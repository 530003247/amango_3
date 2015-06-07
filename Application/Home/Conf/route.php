<?php
return array(
	'URL_ROUTE_RULES' => array( //定义路由规则
		//插件前台
		'Addons/:_addons/:_controller/:_action'         => array('Home/Addons/execute'),
		//插件前台
		'Home/Addons/:_addons/:_controller/:_action'    => array('Home/Addons/execute'),
        //详情
        //http://域名/index.php?s=/Home/Article/detail/id/1.html
        'Article/:id\d'                                 => array('Home/Article/detail'),
        //主题
        //http://域名/index.php?s=/Home/Article/index/category/1.html
        'Articles/:category\d'                          => array('Home/Article/index'),
        //列表
        //http://域名/index.php?s=/Home/Article/lists/category/news.html
        'Articles/:category\D'                          => array('Home/Article/lists'),
	),
);
?>