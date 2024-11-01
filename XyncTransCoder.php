<?php

class XyncTransCoder
{
    //微信图片样式
    private $imageStyle = 'style="max-width: 677px !important;height: auto !important;visibility: visible !important;"';

    public function transcodeContent($content)
    {
        list($content_html, $content_text) = $this->contentHandle($content);
        return ['content_html' => $content_html, 'content_text' => $content_text];
    }
    private function contentHandle($content_html)
    {
        
        //去除掉hidden隐藏
        $content_html = str_replace('style="visibility: hidden;"', '', $content_html);
        //过滤掉iframe
        $content_html = preg_replace('/<iframe(.*?)<\/iframe>/', '', $content_html);
        $path =  content_url().'/uploads/sync-wechat/';
        /** @var  带图片html文本 */
        $content_html = preg_replace_callback('/data-src="(.*?)"/', function ($matches) use ($path) {
            return 'src="' . $path . $this->getImg($matches[1]) . '" ' . $this->imageStyle;
        }, $content_html);

        //添加微信样式
        $content_html = '<div style="max-width: 677px;margin-left: auto;margin-right: auto;">' . $content_html . '</div>';
        /** @var  无图html文本 */
        $content_text = preg_replace('/<img.*?>/s', '', $content_html);

        return [$content_html, $content_text];
    }

    /**
     * @param $url
     * @return string
     */
    private function getImg($url)
    {
        $refer = "http://www.qq.com/";
        $opt = [
            'http' => [
                'header' => "Referer: " . $refer
            ]
        ];
        $context = stream_context_create($opt);
        //接受数据流
        $file_contents = wp_remote_get($url, false, $context);
        $imageSteam =  Imagecreatefromstring($file_contents['body']);
        
        $path = wp_upload_dir()['basedir']."/sync-wechat/";
        if (!file_exists($path))
            mkdir($path, 0777, true);
        $fileName = time() . rand(0, 99999) . '.jpg';
        //生成新图片
        imagejpeg($imageSteam, $path . $fileName);
        return $fileName;
    }
}
