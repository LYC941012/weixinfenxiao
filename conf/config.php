<?php
    return [
        // 显示错误信息
		'show_error_msg'         => true,
		// 是否开启路由
		'url_route_on'           => true,
		// 是否强制使用路由
        'url_route_must'         => false,
		'session' => [
	        'id'             => '',
	        // SESSION_ID的提交变量,解决flash上传跨域
	        'var_session_id' => '',
	        // SESSION 前缀
	        'prefix'         => 'think',
	        // 驱动方式 支持redis memcache memcached
	        'type'           => '',
	        // 是否自动开启 SESSION
	        'auto_start'     => true,
		],
		'template' => [
	        // 模板引擎类型 支持 php think 支持扩展
	        'type'         => 'Think',
	        // 视图基础目录，配置目录为所有模块的视图起始目录
	        'view_base'    => '',
	        // 当前模板的视图目录 留空为自动获取
	        'view_path'    => '',
	        // 模板后缀
	        'view_suffix'  => 'html',
	        // 模板文件名分隔符
	        'view_depr'    => DS,
	        // 模板引擎普通标签开始标记
	        'tpl_begin'    => '{',
	        // 模板引擎普通标签结束标记
	        'tpl_end'      => '}',
	        // 标签库标签开始标记
	        'taglib_begin' => '{',
	        // 标签库标签结束标记
	        'taglib_end'   => '}',
	        //
	        //用于继承模块页面
	//      'layout_on' => true,
	//      'layout_name'=> 'layout',
        ],
    
	    // 视图输出字符串内容替换
	    'view_replace_str'       => [
	      '__IMG__' => '/pcwx/public/static/images'
	    ],
    ]
?>