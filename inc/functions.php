<?php
/*
This file is part of WP XVideos RSS Repost

WP XVideos RSS Repost is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP XVideos RSS Repost is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP XVideos RSS Repost. If not, see <https://www.gnu.org/licenses/gpl.txt>
*/

function print_rss_search_page(){

    echo '<div class="wrap">';

    do_actions();

    //Print the list page
    $myListTable = new RSSVideo_List_Table();
    echo '<h1 class="wp-heading-inline">XVideo RSS News</h1>'; 
    echo '<a href="'.admin_url('edit.php?page=rss-xvideos-posts&action=create_all_posts').'" class="page-title-action">Post All Videos</a>';
    echo '<hr class="wp-header-end">';
    $myListTable->prepare_items(); 
    $myListTable->display(); 
    echo '</div>';

}

function do_actions(){
    $action = $_GET['action'];

    if($action == 'create_post'){
        if(create_post_rss()){
            echo "<div class='notice notice-success is-dismissible'><p><b>Success:</b> Post created</p></div>";
        }else{
            echo "<div class='notice notice-error is-dismissible'><p><b>Failed:</b> Post not created</p></div>";
        }
    }

    if($action == 'create_all_posts'){
        if(create_all_posts()){
            echo "<div class='notice notice-success is-dismissible'><p><b>Success:</b> All posts created</p></div>";
        }else{
            echo "<div class='notice notice-error is-dismissible'><p><b>Failed:</b> Posts not created</p></div>";
        }
    }

    if($action == 'trash'){
        $post_id = $_GET['post'];
        $wpnonce = $_GET['_wpnonce'];

        $post_thumbnail_id = get_post_thumbnail_id( $post_id );

        if(wp_delete_post( $post_id, true ) != false){
            echo "<div class='notice notice-success is-dismissible'><p><b>Success:</b> Post deleted</p></div>";
            if (wp_delete_attachment( $post_thumbnail_id ) === false){
                echo "<div class='notice notice-error is-dismissible'><p>Error deleting image thumbnail</p></div>";
            }
        }else{
            echo "<div class='notice notice-error is-dismissible'><p><b>Failed:</b> Post not deleted</p></div>";
        }
    }
}

function search_rss(){
    //Extract data from Xvideos RSS Channel
    $res = wp_remote_request("https://www.xvideos.com/rss/rss.xml");
    $res['body'] = str_replace("media:keywords", "tags", $res['body']);
    $res['body'] = str_replace("width=510", "width=100%", $res['body']);
    $xml = new SimpleXMLElement($res['body']);
    $items_array = json_decode(json_encode($xml),TRUE);

    return $items_array['channel']['item'];
}

function create_all_posts(){
    $items_search = search_rss();

    foreach ($items_search as $item_post) {
        //Correction for shortened titles
        if(strpos($item_post['title'], "...") > 0 && isset($item_post['link'])){
            $item_post['title'] = strtoupper(str_replace("_", " ", explode("/", $item_post['link'])[sizeof(explode("/", $item_post['link']))-1]));
        }
        if(post_exists($item_post['title']) == 0){
            create_post_rss($item_post);
        }
    }

    return true;
}

function create_post_rss($item=null){

    if(is_null($item)){
        //Get POST Data
        $thumb = esc_sql($_POST['thumb']);
        $title = esc_sql($_POST['title']);
        $link = esc_sql($_POST['link']);
        $embed = $_POST['embed'];
        $duration = esc_sql($_POST['duration']);
        $tags = esc_sql($_POST['tags']);    
    }else{
        //Get Data from $item array
        $thumb = esc_sql($item['thumb_verybig']);
        $title = esc_sql($item['title']);
        $link = esc_sql($item['link']);
        $embed = $item['flv_embed'];
        $duration = esc_sql($item['clips']['duration']);
        $tags = esc_sql($item['tags']);
    }
    

    if(isset($thumb) && isset($title) && isset($embed)){

        //Correction for shortened titles
        if(strpos($title, "...") > 0 && isset($link)){
            $title = strtoupper(str_replace("_", " ", explode("/", $link)[sizeof(explode("/", $link))-1]));
        }

        if(post_exists($title) == 0){

            //Create the Post
            $post_id = wp_insert_post( array(
                'post_title'    => $title,
                'post_content'  => $embed."<br>Title: ".$title."<br>Duration: ".$duration,
                'post_category' => array("XVIDEOS"),
                'post_type'     => 'post',
                'post_status'   => 'publish'
            ));

            if($post_id){

                //Download the thumb image to the server
                $uploaddir = wp_upload_dir();
                $filename = explode("/", $thumb)[sizeof(explode("/", $thumb))-1];
                $uploadfile = $uploaddir['path'].'/'.$filename;

                $contents= file_get_contents($thumb);
                $savefile = fopen($uploadfile, 'w');
                fwrite($savefile, $contents);
                fclose($savefile);

                $filetype = wp_check_filetype( $filename );

                //Add the Thumb Image to Media Library
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => '',
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment( $attachment, $uploadfile, $post_id );

                if($attach_id){

                    //Set the image as Featured Image to the Post
                    $imagenew = get_post( $attach_id );
                    $fullsizepath = get_attached_file( $imagenew->ID );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    set_post_thumbnail( $post_id, $attach_id );

                }else{

                    wp_delete_file( $uploadfile );
                    wp_delete_post( $post_id, true );

                    return false;
                }

                //Set extra information to the post
                if (isset($tags)){
                    wp_set_post_tags( $post_id, $tags );
                }
                if (isset($duration)){
                    add_post_meta( $post_id, 'duration', $duration, true );
                }

            }else{

                return false;
            }

        }else{

            return false;
        }

    }else{

        return false;
    }

    return $post_id;
}


?>