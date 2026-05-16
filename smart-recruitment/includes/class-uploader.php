<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SR_Uploader {

    public function upload_cv( $file ) {
        // Kiểm tra lỗi upload
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return array( 'error' => 'Lỗi upload file' );
        }

        // Chỉ nhận PDF
        $file_type = wp_check_filetype( $file['name'] );
        if ( $file_type['ext'] !== 'pdf' ) {
            return array( 'error' => 'Chỉ chấp nhận file PDF' );
        }

        // Kiểm tra dung lượng tối đa 10MB
        if ( $file['size'] > 10 * 1024 * 1024 ) {
            return array( 'error' => 'File size must not exceed 10MB.' );
        }

        // Tạo folder upload riêng cho plugin
        $upload_dir = wp_upload_dir();
        $cv_dir     = $upload_dir['basedir'] . '/smart-recruitment/cv/';

        if ( ! file_exists( $cv_dir ) ) {
            wp_mkdir_p( $cv_dir );
            // Tạo .htaccess bảo vệ folder
            file_put_contents( $cv_dir . '.htaccess', 'Options -Indexes' );
        }

        // Tạo tên file unique
        $filename  = time() . '_' . sanitize_file_name( $file['name'] );
        $file_path = $cv_dir . $filename;
        $file_url  = $upload_dir['baseurl'] . '/smart-recruitment/cv/' . $filename;

        // Move file
        if ( ! move_uploaded_file( $file['tmp_name'], $file_path ) ) {
            return array( 'error' => 'Không thể lưu file' );
        }

        return array(
            'path' => $file_path,
            'url'  => $file_url,
            'name' => $filename,
        );
    }
}