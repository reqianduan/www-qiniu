<?php 
/**
 * 目标地址：http://www.admin10000.com/UploadFiles/Document/201308/26/20130826112904345812.JPG
 * 正则表达式：/^(http:\/\/)?([^\/]+)/i  $matches[2] ==> $host
 * @param  [type] $matches[0] 图片地址字符串
 * @param  [type] $matches[1] 协议
 * @param  [type] $matches[2] 域名
 * 单独md，图片无法显示，也无法直接url访问；
 * md+img先后包含在content里面，图片无法显示，也无法直接url访问；
 * 单独img，图片能够显示，也能够直接url访问；
 * 使用单独img以后再使用单独md，图片能够显示。
 * 注意点：四次测试都能够生成md5，切全部一致，未发生变化。
 * 结论：只有img才执行了图片上传到七牛的动作。
 */



	if(wpjam_qiniutek_get_setting('remote') && get_option('permalink_structure')){
		add_filter('the_content', 		'wpjam_qiniutek_content',1);
		add_filter('the_content', 		'wpjam_qiniutek_content_md',1);
		add_filter('query_vars', 		'wpjam_qiniutek_query_vars');
		add_action('template_redirect',	'wpjam_qiniutek_template_redirect', 5);
	}

	$target = '
[0]: http://www.manong1024.com/uploads/article/20141208/caf64c1740e8e8d86a1ede6cbac3b5d3.jpg
[1]: http://uec.nq.com/works/162
[2]: http://s1.nq.com/file/1418613990767/760-260.jpg
[3]: http://s1.nq.com/img/cnnq/20141215/20141215113117_mobile_os_mini.jpg';

	/**
	 * 
	 * 使用正则匹配$content的内容，获得matches，传入回调函数
	 * wpjam_qiniutek_replace_remote_image：回调
	 */
	function wpjam_qiniutek_content($content){
		return preg_replace_callback('|<img.*?src=[\'"](.*?)[\'"].*?>|i', 'wpjam_qiniutek_replace_remote_image', do_shortcode($content)); //返回回调函数的结果？
	}
	function wpjam_qiniutek_content_md($content){
		return preg_replace_callback('/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|gif|png)/i', 'wpjam_qiniutek_replace_remote_image_md', do_shortcode($content)); //返回回调函数的结果？
	}

	/**
	 * 主函数：替换img里面的图片地址
	 * @param  [type] $matches[0] img元素字符串
	 * @param  [type] $matches[1] src里面的url
	 */
	function wpjam_qiniutek_replace_remote_image($matches){
		$qiniu_image_url = $image_url = $matches[1];

		if(empty($image_url)) return;

		$pre = apply_filters('pre_qiniu_remote', false, $image_url);

		if($pre == false && strpos($image_url,LOCAL_HOST) === false && strpos($image_url,CDN_HOST) === false ){
			$img_type = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));

			if($img_type != 'gif'){
				$img_type = ($img_type == 'png')?$img_type:'jpg';

				$md5 = md5($image_url);
				$qiniu_image_url = CDN_HOST.'/qiniu/'.get_the_ID().'/image/'.$md5.'.'.$img_type;
			}
		}

		$width = (int)wpjam_qiniutek_get_setting('width');

		if($width){
			if(preg_match('|<img.*?width=[\'"](.*?)[\'"].*?>|i',$matches[0],$width_matches)){
				$width = $width_matches[1];
			}

			$height = 0;

			if(preg_match('|<img.*?height=[\'"](.*?)[\'"].*?>|i',$matches[0],$height_matches)){
				$height = $height_matches[1];
			}

			if($width || $height){
				$qiniu_image_url = wpjam_get_qiniu_thumbnail($qiniu_image_url, $width, $height, 0);
			}
		}

		$pre = apply_filters('pre_qiniu_watermark', false, $image_url);

		if($pre == false ){
			$qiniu_image_url = wpjam_get_qiniu_watermark($qiniu_image_url);
		}

		return str_replace($image_url, $qiniu_image_url, $matches[0]);//从$matches[0](img元素字符串)里搜索$image_url并替换为$qiniu_image_url，之后返回img元素字符串
	}
	/**
	 * 主函数：替换markdown里面的图片地址
	 * @param  [type] $matches[0] 图片地址字符串
	 * @param  [type] $matches[1] 协议
 	 * @param  [type] $matches[2] 域名
	 */
	function wpjam_qiniutek_replace_remote_image_md($matches){
		$qiniu_image_url = $image_url = $matches[0];

		if(empty($image_url)) return;

		$pre = apply_filters('pre_qiniu_remote', false, $image_url);

		if($pre == false && strpos($image_url,LOCAL_HOST) === false && strpos($image_url,CDN_HOST) === false ){
			$img_type = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));

			if($img_type != 'gif'){
				$img_type = ($img_type == 'png')?$img_type:'jpg';

				$md5 = md5($image_url);
				$qiniu_image_url = CDN_HOST.'/qiniu/'.get_the_ID().'/image/'.$md5.'.'.$img_type;
			}
		}

		$pre = apply_filters('pre_qiniu_watermark', false, $image_url);

		if($pre == false ){
			$qiniu_image_url = wpjam_get_qiniu_watermark($qiniu_image_url);
		}

		return str_replace($image_url, $qiniu_image_url, $matches[0]);//从$matches[0](img元素字符串)里搜索$image_url并替换为$qiniu_image_url，之后返回img元素字符串
	}

 ?>