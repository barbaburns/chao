<?php

namespace App;

class ArchiveQuery
{
    public static function filterMainQuery($query)
    {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_post_type_archive('autores')) {
            $query->set('orderby', 'menu_order');
            $query->set('order', 'ASC');
            return;
        }

        if ($query->is_category() || $query->is_tag()) {
            $postTypes = $query->get('post_type');

            if ($postTypes !== 'any') {
                $basePostTypes = empty($postTypes) ? ['post'] : (array) $postTypes;
                $query->set('post_type', array_values(array_unique(array_merge($basePostTypes, ['manuscrito']))));
            }
        }

        if ($query->is_home()) {
            $args = [
                [
                    'key'       => 'hide_post',
                    'value'     => 1,
                    'compare'   => '!='
                ]
            ];

            $query->set('meta_query', $args);
        }
    }
}
