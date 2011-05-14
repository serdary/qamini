<?php 
echo View::factory($theme_dir.'partials/post_form')
             ->set('user_logged_in', $user_logged_in)
             ->set('theme_dir', $theme_dir)
             ->bind('notify_user', $notify_user)
             ->bind('errors', $errors)
             ->set('post', $post)
             ->set('form_type', Helper_PostType::QUESTION)
             ->set('form_action', URL::site(Route::get('question')->uri(array('action' => 'ask'))))
             ->set('form_title', __('Ask Question'))
             ->set('tag_list', $tag_list)
             ->set('button_value', __('Add'))
             ->set('token', $token)
             ->render();