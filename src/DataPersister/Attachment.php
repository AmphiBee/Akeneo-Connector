<?php

namespace AmphiBee\AkeneoConnector\DataPersister;

use WP_Error;

/**
 * @TODO delete this class, use ProductDataPersister instead
 */
class Attachment
{

    const MAX_ATTACHMENT_SIZE = 0;

    /**
     * Set product medias settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    public static function registerProductAttachment(object &$product, array $args)
    {
        // Images and Gallery
        if (isset($args['images']) && is_array($args['images'])) {
            $image_id = self::assignRemoteAttachment($args['images'][0]);
            $product->set_image_id($image_id ? $image_id : "");
        }

        $gallery_ids = [];

        if (count($args['images']) > 1) {
            array_shift($args['images']);
            foreach ($args['images'] as $img) {
                $gallery_ids[] = self::assignRemoteAttachment($img);
            }
        }

        $product->set_gallery_image_ids($gallery_ids);
    }

    /**
     * If fetching attachments is enabled then attempt to create a new attachment
     *
     * @param string $url | URL to fetch attachment from
     * @param array $parent_id | Post related to the attachment
     * @return int|\WP_Error | Post ID on success, WP_Error otherwise
     */
    public static function assignRemoteAttachment(string $url, int $parent_id = 0)
    {

        $upload = self::fetchRemoteFile($url);
        $hash_file = md5(hash_file('md5', $upload['file']));

        if (\is_wp_error($upload)) {
            return $upload;
        }

        // detect if file already exists
        if ($post_id = self::filehashAlreadyExist($hash_file)) {
            self::rawDeleteFile($upload['file']);
            return $post_id;
        }

        if ($info = \wp_check_filetype($upload['file'])) {
            $post['post_mime_type'] = $info['type'];
        } else {
            return new WP_Error('attachment_processing_error', __('Invalid file type', 'wordpress-importer'));
        }

        $post_id = \wp_insert_attachment($post, $upload['file'], $parent_id);

        update_post_meta($post_id, 'hash_file', $hash_file);

        \wp_update_attachment_metadata($post_id, wp_generate_attachment_metadata($post_id, $upload['file']));

        return $post_id;
    }

    /**
     * Detect if hash file already in the database
     *
     * @param string $hash_file : Hash of the file
     * @return int | ID of post related to the hash
     */
    private static function filehashAlreadyExist(string $hash_file) : int
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value = %s", $hash_file);
        $file_obj = $wpdb->get_row($query);
        return !is_null($file_obj) ? $file_obj->post_id : 0;
    }

    private static function getHttp(string $url, $file_path = false)
    {
        @set_time_limit(60);

        $options = [
            'redirection' => 5,
            'method' => 'GET'
        ];

        $response = \wp_safe_remote_request($url, $options);

        if (\is_wp_error($response)) {
            return false;
        }

        $headers = \wp_remote_retrieve_headers($response);
        $headers['response'] = \wp_remote_retrieve_response_code($response);

        if (false == $file_path) {
            return $headers;
        }

        // GET request - write it to the supplied filename
        $out_fp = fopen($file_path, 'w');
        if (!$out_fp) {
            return $headers;
        }

        fwrite($out_fp, \wp_remote_retrieve_body($response));
        fclose($out_fp);
        clearstatcache();

        return $headers;
    }

    /**
     * Attempt to download a remote file attachment
     *
     * @param string $url URL of item to fetch
     * @return array|WP_Error Local file location details on success, WP_Error otherwise
     */
    public static function fetchRemoteFile(string $url)
    {
        // extract the file name and extension from the url
        $file_name = basename($url);

        // get placeholder file in the upload dir with a unique, sanitized filename
        $upload = \wp_upload_bits($file_name, 0, '');
        if ($upload['error']) {
            return new WP_Error('upload_dir_error', $upload['error']);
        }

        // fetch the remote url and write it to the placeholder file
        $headers = self::getHttp($url, $upload['file']);

        // request failed
        if (!$headers) {
            self::rawDeleteFile($upload['file']);
            return new WP_Error('import_file_error', __('Remote server did not respond', 'wordpress-importer'));
        }

        // make sure the fetch was successful
        if ('200' != $headers['response']) {
            self::rawDeleteFile($upload['file']);
            return new WP_Error('import_file_error', sprintf(__('Remote server returned error response %1$d %2$s', 'wordpress-importer'), esc_html($headers['response']), get_status_header_desc($headers['response'])));
        }

        $filesize = filesize($upload['file']);

        if (isset($headers['content-length']) && $filesize != $headers['content-length']) {
            self::rawDeleteFile($upload['file']);
            return new WP_Error('import_file_error', __('Remote file is incorrect size', 'wordpress-importer'));
        }

        if (0 == $filesize) {
            self::rawDeleteFile($upload['file']);
            return new WP_Error('import_file_error', __('Zero size file downloaded', 'wordpress-importer'));
        }

        $max_size = self::MAX_ATTACHMENT_SIZE;
        if (!empty($max_size) && $filesize > $max_size) {
            self::rawDeleteFile($upload['file']);
            return new WP_Error('import_file_error', sprintf(__('Remote file is too large, limit is %s', 'wordpress-importer'), size_format($max_size)));
        }

        return $upload;
    }

    public static function rawDeleteFile($path)
    {
        return @unlink($path);
    }
}
