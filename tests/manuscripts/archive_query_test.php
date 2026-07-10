<?php

namespace App {
    $mockIsAdmin = false;

    function is_admin()
    {
        global $mockIsAdmin;

        return $mockIsAdmin;
    }
}

namespace {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    use App\ArchiveQuery;

    class MockArchiveQuery
    {
        public $values = [];
        public $main = true;
        public $home = false;
        public $category = false;
        public $tag = false;
        public $postTypeArchive = null;

        public function is_main_query()
        {
            return $this->main;
        }

        public function is_home()
        {
            return $this->home;
        }

        public function is_category()
        {
            return $this->category;
        }

        public function is_tag()
        {
            return $this->tag;
        }

        public function is_post_type_archive($postType = '')
        {
            return $this->postTypeArchive === $postType;
        }

        public function set($key, $value)
        {
            $this->values[$key] = $value;
        }

        public function get($key)
        {
            return isset($this->values[$key]) ? $this->values[$key] : null;
        }
    }

    function assertSameValue($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            fwrite(STDERR, $message . PHP_EOL);
            fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
            fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
            exit(1);
        }
    }

    function assertTrueValue($condition, $message)
    {
        if (!$condition) {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }
    }

    global $mockIsAdmin;

    $categoryQuery = new MockArchiveQuery();
    $categoryQuery->category = true;
    ArchiveQuery::filterMainQuery($categoryQuery);
    assertSameValue(
        ['post', 'manuscrito'],
        $categoryQuery->get('post_type'),
        'Category archives should include manuscript posts alongside regular posts'
    );

    $tagQuery = new MockArchiveQuery();
    $tagQuery->tag = true;
    ArchiveQuery::filterMainQuery($tagQuery);
    assertSameValue(
        ['post', 'manuscrito'],
        $tagQuery->get('post_type'),
        'Tag archives should include manuscript posts alongside regular posts'
    );

    $authorsQuery = new MockArchiveQuery();
    $authorsQuery->postTypeArchive = 'autores';
    ArchiveQuery::filterMainQuery($authorsQuery);
    assertSameValue('menu_order', $authorsQuery->get('orderby'), 'Author archives should keep menu order sorting');
    assertSameValue('ASC', $authorsQuery->get('order'), 'Author archives should keep ascending sorting');

    $homeQuery = new MockArchiveQuery();
    $homeQuery->home = true;
    ArchiveQuery::filterMainQuery($homeQuery);
    assertTrueValue(is_array($homeQuery->get('meta_query')), 'Home query should keep the hidden-post meta filter');

    $nonMainQuery = new MockArchiveQuery();
    $nonMainQuery->main = false;
    $nonMainQuery->category = true;
    ArchiveQuery::filterMainQuery($nonMainQuery);
    assertSameValue(null, $nonMainQuery->get('post_type'), 'Non-main category queries should remain untouched');

    $mockIsAdmin = true;
    $adminQuery = new MockArchiveQuery();
    $adminQuery->category = true;
    ArchiveQuery::filterMainQuery($adminQuery);
    assertSameValue(null, $adminQuery->get('post_type'), 'Admin category queries should remain untouched');

    echo 'archive_query_test: ok' . PHP_EOL;
}
