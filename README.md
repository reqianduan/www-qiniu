# 七牛镜像存储 WordPress 插件

为了支持markdown语法，对原版的远程图片保存功能进行了修改。

## 匹配图片的正则表达式
```
|<img.*?src=[\'"](.*?)[\'"].*?>|i
```
修改为：
```
/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|jpeg|gif|png)/i
```

## 获取缩略图
```
if(!function_exists('get_post_first_image')){
    function get_post_first_image($post_content){
        preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post_content, $matches);
        if($matches){    
            return $matches[1][0];
        }else{
            return false;
        }
    }
}
```
修改为
```
if(!function_exists('get_post_first_image')){
    function get_post_first_image($post_content){
        preg_match_all('/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|jpeg|gif|png)/i', $post_content, $matches);
        if($matches){    
            return $matches[0][0];
        }else{
            return false;
        }
    }
}
```

## 自动远程图片镜像到七牛
以下部分重写为同名_md的算法，具体查看git记录
```
add_filter('the_content',       'wpjam_qiniutek_content',1);

function wpjam_qiniutek_content($content){
    return preg_replace_callback('|<img.*?src=[\'"](.*?)[\'"].*?>|i', 'wpjam_qiniutek_replace_remote_image', do_shortcode($content));
}

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

    return str_replace($image_url, $qiniu_image_url, $matches[0]);
}
```
