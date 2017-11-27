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

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RSSVideo_List_Table extends WP_List_Table {

    function get_columns(){

        $columns = array(
            'thumb_small'   => 'Thumbnail',
            'title'         => 'Title',
            'clips'         => 'Duration',
            'tags'          => 'Tags',
            'create_post'   => 'Create Post'
        );

        return $columns;
    }

    function prepare_items(){

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = search_rss();
    }

    function column_default( $item, $column_name ) {

        switch( $column_name ) { 

            case 'thumb_small':
                return "<a target='_blank' href='".$item['link']."'><img src='".$item[$column_name]."' /></a>";

            case 'title':
                return "<a target='_blank' href='".$item['link']."'>".$item[$column_name]."</a>";

            case 'clips':
                return $item['clips']['duration'];

            case 'tags':
                return $item['tags'];

            case 'create_post':
                //Correction for shortened titles
                if(strpos($item['title'], "...") > 0 && isset($item['link'])){
                    $item['title'] = strtoupper(str_replace("_", " ", explode("/", $item['link'])[sizeof(explode("/", $item['link']))-1]));
                }
                $id_post_exist = post_exists($item['title']);
                if( $id_post_exist == 0){
                    return "
                        <form action='".admin_url('edit.php?page=rss-xvideos-posts&action=create_post')."' method='POST'>
                        <input type='hidden' name='thumb' value='".$item['thumb_verybig']."' />
                        <input type='hidden' name='title' value='".$item['title']."' />
                        <input type='hidden' name='link' value='".$item['link']."' />
                        <input type='hidden' name='embed' value='".$item['flv_embed']."' />
                        <input type='hidden' name='duration' value='".$item['clips']['duration']."' />
                        <input type='hidden' name='tags' value='".$item['tags']."' />
                        <input type='submit' value='CREATE POST' class='button button-primary' />
                        </form>
                    ";
                }else{
                    return "
                        Post already exists<br>
                        <a href='".site_url(strtolower(str_replace(" ", "-", $item['title'])))."' target='_blank'>View</a> - 
                        <a href='".admin_url('post.php?post='.$id_post_exist.'&action=edit')."' target='_blank'>Edit</a> - 
                        <a style='color:red' href='".wp_nonce_url(admin_url('edit.php?page=rss-xvideos-posts&post='.$id_post_exist.'&action=trash'), 'trash-post_-'.$id_post_exist)."'>Delete</a>
                    ";
                }

            default:
                return "";

        }

    }

}
?>