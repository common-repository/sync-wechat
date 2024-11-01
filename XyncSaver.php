<?php
class XyncSaver
{

    function saveArticle($title, $imageUrl, $content, $postType, $updateTime)
    {
        $new_post = array(
            "post_title" => $title ? $title : "no title",
            "post_content" => $content,
            "post_status" => "publish",
            "post_date" => date('Y-m-d H:i:s', $updateTime),
            "post_author" => "admin",
            "post_type" => $postType,
        );

        $post_id = wp_insert_post($new_post);

        // Add Featured Image to Post
        $image_name       = date_timestamp_get(date_create()) . '.jpg';
        // $image_name       = 'wp-header-logo.png';
        $upload_dir       = wp_upload_dir(); // Set upload folder
        $image_data       = $imageUrl ? wp_remote_get($imageUrl)['body'] : ''; // Get image data
        if ($imageUrl) {
            $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
            $filename         = basename($unique_file_name); // Create image file name

            // Check folder permission and define file location
            if (wp_mkdir_p($upload_dir['path'])) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }

            // Create the image file on the server
            file_put_contents($file, $image_data);

            // Check image file type
            $wp_filetype = wp_check_filetype($filename, null);

            // Set attachment data
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => sanitize_file_name($filename),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            // Create the attachment
            $attach_id = wp_insert_attachment($attachment, $file, $post_id);

            // Define attachment metadata
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);

            // Assign metadata to attachment
            wp_update_attachment_metadata($attach_id, $attach_data);

            // And finally assign featured image to post
            set_post_thumbnail($post_id, $attach_id);

            
        }
        // wp_cache_flush();
    }
}
