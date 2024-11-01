<?php
require('XyncSaver.php');
require('XyncTransCoder.php');
class XyncManager
{
    /**
     * @param appId
     * @param appSecret
     * @api: https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
     */
    function getToken($appId, $appSecret)
    {
        $tokenAccessUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential" . "&appid=" . $appId . "&secret=" . $appSecret;
        $res = wp_remote_get($tokenAccessUrl); //获取文件内容或获取网络请求的内容
                
        $result = json_decode($res['body'], true); //接受一个 JSON 格式的字符串并且把它转换为 PHP 变量
        if ($result['errcode'] == 40164) {
            return json_encode(
                array(
                    'code' => $result['errcode'],
                    'data' => $result['errmsg']
                )
            );
        } else if ($result['access_token'] == null) {
            return 'token not found';
        } else {
            return $result['access_token'];
        }
    }

    /**
     * @param token
     * @api: https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=TOKEN
     */
    function getMaterialCount($token)
    {
        $token_access_url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=" . $token;
        $res = wp_remote_get($token_access_url); //获取文件内容或获取网络请求的内容
        //echo $res;
        return json_decode($res['body'], true); //接受一个 JSON 格式的字符串并且把它转换为 PHP 变量
    }

    function array_to_object($arr)
    {
        if (gettype($arr) != 'array') {
            return;
        }
        foreach ($arr as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object') {
                $arr[$k] = (object)$this->array_to_object($v);
            }
        }

        return (object)$arr;
    }

    function getOffset($token, $endTimestamp)
    {
        $token_access_url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=" . $token;
        $res = wp_remote_get($token_access_url);

        $totalArticleNum = json_decode($res['body'], true)['news_count'];
        $batchNum = 20;
        $roundNum = ceil($totalArticleNum / $batchNum);
        $offset = 0;
        $fount = false;

        for ($i = 0; $i < $roundNum; $i++) {
            $args = json_encode(array(
                'type' => 'news',
                'offset' => $i * $batchNum,
                'count' => $batchNum
            ));

            $res = wp_remote_post(
                "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $token,
                [
                    'body'   => $args,
                ]
            );
            $res_arr = json_decode($res['body']);

            

            foreach ($res_arr->item as $item) {

                if ($item->content->update_time <= $endTimestamp) {
                    $fount = true;
                    break;
                }
                $offset++;
            }
            if ($fount) {
                break;
            }
        }
        if ($fount) {
            echo json_encode(array(
                'code' => 200,
                'total' => $totalArticleNum,
                'offset' => $offset
            ));
        } else {
            echo json_encode(array(
                'code' => 404,
                'msg' => 'not article found'
            ));
        }
    }


    /**
     * @param token
     * @param appSecret
     * @api: https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=TOKEN
     */
    function saveArticle($token, $postType, $offset, $startTimestamp, $endTimestamp)
    {
        $res = wp_remote_post(
            "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $token,
            [
                'body'   => json_encode(array(
                    'type' => 'news',
                    'offset' => $offset,
                    'count' => 1
                )),
            ]
        );

        $httpCode = $res['response']['code'];
        $response = $res['body'];

        $saver = new XyncSaver();
        $transCoder = new XyncTransCoder();

        $articleTitle = "";
        $articleImageUrl = "";
        $articleContent = ""; // transfer images and save them

        if ($httpCode == 200) {
            $res_arr = json_decode($response);
            $item = $res_arr->item[0];
            $updateTime = $item->content->update_time;

            if ($endTimestamp >= $updateTime) {
                if ($startTimestamp <= $updateTime) {
                    $articleTitle = ($item->content->news_item)[0]->title;
                    $articleImageUrl = ($item->content->news_item)[0]->thumb_url;
                    $articleContent = ($item->content->news_item)[0]->content;
                    $articleContent = $transCoder->transcodeContent($articleContent)['content_html']; // transfer images and save them                
                    $saver->saveArticle($articleTitle, $articleImageUrl, $articleContent, $postType, $updateTime);
                    echo "round done";
                } else {
                    echo "sync finished";
                }
            } else {
                echo "keep seeking";
            }
        }
    }

    /**
     * @param token
     * @param mediaId
     * @api: https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=TOKEN
     */
    function getArticleByMediaId($token, $mediaId)
    {
        $args = array(
            'access_token' => $token,
            'media_id'     => $mediaId,
        );
        $res = wp_remote_post('https://api.weixin.qq.com/cgi-bin/material/get_material', $args);


        $httpCode = $res[0];
        $response = $res[1];
        if ($httpCode == 200) {
            $res = json_decode($response);
            return $this->objToArrary($res)["news_item"][0];
        }
    }

    function objToArrary($obj)
    {
        $ret = array();
        foreach ($obj as $key => $value) {
            if (gettype($value) == 'array' || gettype($value) == 'object') {
                $ret[$key] = $this->objToArrary($value);
            } else {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }
}
