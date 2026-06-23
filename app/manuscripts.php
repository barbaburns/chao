<?php

namespace App;

use App\Manuscripts\Admin;
use App\Manuscripts\Frontend;
use App\Manuscripts\PostType;

add_action('init', [PostType::class, 'register']);
add_action('admin_init', [Admin::class, 'register']);
add_action('init', [Frontend::class, 'register']);
