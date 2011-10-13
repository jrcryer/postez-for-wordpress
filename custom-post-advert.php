<?php
/*
Plugin Name: Custom Post Advert
Version: 1.0
Plugin URI: http://www.jamescryer.com
Description: Allows a user to display specific custom post types in a widget
Author: James Cryer
Author URI: http://www.jamescryer.com
*/

class CustomPostAdvertWidget extends WP_Widget {
    
    public $aSortOrder = array(
        'none'     => '',
        'ID'       => 'Post ID',
        'author'   => 'Author',
        'title'    => 'Title',
        'date'     => 'Date',
        'modified' => 'Modified',
        'parent'   => 'Post / Page Parent ID',
        'rand'     => 'Random'
    );
    
    public function CustomPostAdvertWidget() {
        parent::WP_Widget(false, $name = 'Custom Post Advert');
    }

    public function form($instance) {
        $title          = esc_attr($instance['title']);
        $customType     = esc_attr($instance['custom-post-type']);
        $id             = esc_attr($instance['identifier']);
        $limit          = esc_attr($instance['limit']);
        $order          = esc_attr($instance['order']);
        $pageUrl        = esc_attr($instance['destination-page']);

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('destination-page'); ?>"><?php _e('Title URL:'); ?>
                <?php wp_dropdown_pages(array(
                    'name'             => $this->get_field_name('destination-page'),
                    'show_option_none' => ' ',
                    'selected'         => $pageURL
                )); ?>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('custom-post-type'); ?>"><?php _e('Content Type:'); ?>
                <?php $aPostTypes = get_post_types(); ?>
                
                <select class="widefat" id="<?php echo $this->get_field_id('custom-post-type'); ?>" name="<?php echo $this->get_field_name('custom-post-type'); ?>">
                    <?php
                       
                       foreach($aPostTypes as $key => $value) {
                           if(!in_array($key, array('nav_menu_item', 'revision'))) {
                               $obj = get_post_type_object($key);
                               ?><option value="<?php echo $key; ?>" <?php selected($customType, $key); ?>><?php echo $obj->labels->name; ?></option><?php
                           }
                       }
                    ?>
                </select>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('identifier'); ?>"><?php _e('Item ID(optional):'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('identifier'); ?>" name="<?php echo $this->get_field_name('identifier'); ?>" type="text" value="<?php echo $id; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of items:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Order:'); ?>
                
                <select class="widefat" id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
                    <?php
                       
                       foreach($this->aSortOrder as $key => $value) {
                           ?><option value="<?php echo $key; ?>" <?php selected($order, $key); ?>><?php echo $value; ?></option><?php
                       }
                    ?>
                </select>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category:'); ?>
                <?php wp_dropdown_categories(array(
                    'show_option_none' => ' '
                )); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Update a widget values
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
	$instance['title']            = strip_tags($new_instance['title']);
        $instance['destination-page'] = strip_tags($new_instance['destination-page']);
        $instance['custom-post-type'] = strip_tags($new_instance['custom-post-type']);
        $instance['identifier']       = strip_tags($new_instance['identifier']);
        $instance['limit']            = strip_tags($new_instance['limit']);
        $instance['order']            = strip_tags($new_instance['order']);
        return $instance;
    }

    /**
     * Process the widget and output the content
     * 
     * @param array $args
     * @param array $instance
     * @return string
     */
    public function widget($args, $instance) {
        extract($args);
        
        echo sprintf(
            "%s%s%s%s%s%s",
            $before_widget,
                $before_title,
                    $this->getTitle($instance),
                $after_title,
                $this->getContent($instance),
            $after_widget
        );
    }

    /**
     * Returns the HTML for the title of the widget
     * 
     * @param array $instance
     * @return string
     */
    protected function getTitle($instance) {
        $title    = apply_filters('widget_title', $instance['title']);
        
        $query    = new WP_Query("page_id={$instance['destination-page']}");
        
        if(!$query->have_posts()) {
            return $title;
        }
        $query->the_post();
        $title =  sprintf(
            "<a href=\"%s\">%s</a>", get_permalink(), $title
        );       
        wp_reset_postdata();
        return $title;
    }

    /**
     * Generates HTML list of feed items
     *
     * @param array $instance
     * @return string
     */
    protected function getContent($instance) {
        
        $aPost = array();
        if($instance['identifier']) {
            $aPost = $this->getSingleItem($instance);
        }
        else {
            $aPost = $this->getMultipleItems($instance);
        }
        return $this->generateWidgetContent($instance, $aPost);
    }
    
    /**
     * Extracts a single post
     * 
     * @param array $instance
     * @return array 
     */
    protected function getSingleItem($instance) {
        $id    = $instance['identifier'];
        $type  = $instance['custom-post-type'];
        $query = new WP_Query(array(
            'p' => $id,
            'post_type' => $type
        ));
        
        if(!$query->have_posts()) {
            return '';
        }
        
        $query->the_post();
        $aPost[] = array(
            'title'     => get_the_title(),
            'excerpt'   => get_the_excerpt(),
            'permalink' => get_permalink(),
            'class'     => get_post_class()
        );
        wp_reset_postdata();
        return $aPost;
    }
    
    /**
     * Extracts multiple posts
     * 
     * @param array $instance
     * @return array 
     */
    protected function getMultipleItems($instance) {
        $type     = $instance['custom-post-type'];
        $limit    = $instance['limit'];
        $order    = $instance['order'];
        $category = $instance['category'];
        
        $args     = array(
            'post_type'      => $type,
            'posts_per_page' => $limit ? $limit : '',
            'orderby'        => $order,
            'order'          => 'ASC'
        );
        $query    = new WP_Query( $args );
        
        while($query->have_posts()) {
            $query->the_post();
            $aPost[] = array(
                'title'     => get_the_title(),
                'excerpt'   => get_the_excerpt(),
                'permalink' => get_permalink(),
                'class'     => get_post_class()
            );
        }
        wp_reset_postdata();
        return $aPost;
    }
    
    /**
     * Generates HTML for widget content
     * 
     * @param array $instance
     * @param array $aPost
     * @return string
     */
    protected function generateWidgetContent($instance, $aPost) {
        
        $type     = $instance['custom-post-type'];
        if(empty($aPost)) {
            return '';
        }
        $content = "<ul class=\"posts {$type}\">";
        
        foreach($aPost as $post) {
            $content .= "<li class=\"{$post['class']}\">";
            $content .= "<h3><a href=\"{$post['permalink']}\">{$post['title']}</a></h3>";
            $content .= "<div class=\"description\">{$post['excerpt']}</div>";
            $content .= "<div class=\"read-full\"><a href=\"{$post['permalink']}\">Read more</a></div>";
            $content .= "</li>";
        }
        $content .= "</ul>";
        return $content;
    }
}
add_action('widgets_init', create_function('', 'return register_widget("CustomPostAdvertWidget");'));