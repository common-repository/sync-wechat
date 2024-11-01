<div class="xync-dashboard">

    <div method="post" action="">
        <h1>微信公众号同步助手</h1>
        <?php
        $xyncOptions = get_option('sync_wechat_options');
        $appId = $xyncOptions['app_id'];
        $appSecret = $xyncOptions['app_secret'];
        ?>
        <div class="main-form">
            <table class="xync-collector">
                <tr>
                    <td>App Id: </td>
                    <td><input type="text" name="appId" value="<?php esc_html_e($appId) ?>" /></td>
                </tr>
                <tr>
                    <td>App Secret: </td>
                    <td><input type="text" name="appSecret" value="<?php esc_html_e($appSecret) ?>" /></td>
                </tr>

                <tr>
                    <td>日期范围: </td>
                    <td><input type="" id="datePicker" value=""></td>
                </tr>

                <tr>
                    <td class="pt-0-5" valign="top">同步到文章类别: </td>
                    <td><input type="text" name="articleType" value="post" /> <br> (请输入文章类别slug)</td>
                </tr>
            </table>
            <button id="sync-button" class="button button-primary button-large">同步</button>

            <div class="message">
                <div class="sync-message">
                    同步中，请稍后... <img src="<?php esc_html_e(content_url()); ?>/plugins/sync-wechat/images/loading.gif" alt="loading" class="sync-loading">
                    <br>
                    已同步<span class="sync-article-num">0</span>篇文章
                    <br>
                    <span class="sync-message--notice">注意：请不要关闭此页面</span>
                </div>

                <div class="sync-message--finish">
                    同步完成
                </div>
                <div class="sync-message--not-found">
                    没找到文章
                </div>

                <div class="sync-message--fail">
                    <div class="sync-message--fail-message">
                        好像出现了问题
                        <img style="width: 1.5rem;" src="<?php esc_html_e(content_url()); ?>/plugins/sync-wechat/images/thinking.png" alt="thinking" />
                        请联系我们 <a href="#">好势发生科技</a>
                        <img style="width: 1.5rem;" src="<?php esc_html_e(content_url()); ?>/plugins/sync-wechat/images/ok.png" alt="ok" />
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>



<script>
    /* --- Advanced Settings ---
    var advancedSettingsShowed = false
    $(".advanced-settings-trigger").click(function() {
        if (!advancedSettingsShowed)
            $('.advanced-settings').slideDown();
        else
            $('.advanced-settings').slideUp();
        advancedSettingsShowed = !advancedSettingsShowed
    });
    */
    var startDate = "";
    var endDate = new Date();
    var startTimestamp = null;
    var endTimestamp = null;
    var articlesTotalNum = null;
    var appId = null;
    var appSecret = null;
    var foundArticle = false;
    var momentFun = null;

    jQuery(document).ready(function() {
        startDate = moment().subtract(6, 'days');
        startTimestamp = new Date(moment().subtract(6, 'days').format('YYYY-MM-DD 00:00:00')).valueOf() / 1000;
        endTimestamp = parseInt(moment().endOf("day").valueOf() / 1000);
        articlesTotalNum = 0;
        momentFun = moment();
        foundArticle = false;

        jQuery("#sync-button").click(function() {
            appId = jQuery("input[name='appId']").val()
            appSecret = jQuery("input[name='appSecret']").val()
            jQuery(".sync-message").fadeIn();
            jQuery(".sync-message--finish").hide();
            jQuery('.sync-message--fail').hide();
            jQuery(".sync-message--not-found").hide();
            startSync();
        });


        if (momentFun) {
            jQuery('#datePicker').daterangepicker({
                locale: {
                    "format": 'YYYY-MM-DD',
                    "separator": " - ",
                    "applyLabel": "确定",
                    "cancelLabel": "取消",
                    "resetLabel": "重置",
                    "fromLabel": "起始时间",
                    "toLabel": "结束时间'",
                    "customRangeLabel": "自定义",
                    "weekLabel": "W",
                    "daysOfWeek": ["日", "一", "二", "三", "四", "五", "六"],
                    "monthNames": ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    "firstDay": 1
                },
                ranges: {
                    '今日': [moment(), moment()],
                    '昨日': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '最近7日': [moment().subtract(6, 'days'), moment()],
                    '最近30日': [moment().subtract(29, 'days'), moment()],
                    '本月': [moment().startOf('month'), moment().endOf('month')],
                    '上月': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    '今年': [moment().startOf('year'), moment().endOf('year')],
                },
                "alwaysShowCalendars": true,
                "startDate": startDate, //7天前
                "endDate": endDate, //现在
                "opens": "right",
            }, function(start, end, label) {
                console.log(start.format('YYYY-MM-DD'))
                startTimestamp = start.valueOf() / 1000
                console.log('start timestamp', startTimestamp)
                console.log(end.format('YYYY-MM-DD'))
                endTimestamp = parseInt(end.valueOf() / 1000)
                console.log('end timestamp', endTimestamp)
                console.log(label)
            });
        }

    });

    function startSync() {
        jQuery('.sync-article-num').text(0)
        var data = {
            action: 'sync_wechat_check_validation',
            app_id: appId,
            app_secret: appSecret,
            end_timestamp: endTimestamp
        };
        jQuery.post(ajaxurl, data).done(function(response) {  
            var res = resFilter(response);
            if (res.code != 404) {
                coreSyncProcess(res.offset, res.total)
            } else {
                articleNotFound();
            }
            console.log('startSync done')
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log('startSync fail')
            // show 503
            jQuery('.sync-message').hide();
            jQuery('.sync-message--fail').fadeIn();
            console.log(errorThrown)
        });
    }

    function resFilter(response) {
        if (response[response.length - 1] != "}") {
            return JSON.parse(response.substr(0, response.length - 1))
        } else {
            return JSON.parse(response)
        }
    }

    function articleNotFound() {
        jQuery(".sync-message").hide();
        jQuery(".sync-message--finish").hide();
        jQuery('.sync-message--fail').hide();
        jQuery(".sync-message--not-found").fadeIn();
        console.log("not found");
    }

    var syncCount = 0;

    function coreSyncProcess(offset, articlesTotalNum) {
        var data = {
            action: 'sync_wechat_core_sync_process',
            app_id: appId,
            app_secret: appSecret,
            offset,
            post_type: jQuery("input[name='articleType']").val(),
            start_timestamp: startTimestamp,
            end_timestamp: endTimestamp,
        };
        jQuery.post(ajaxurl, data).done(function(response) {   
            var res = response;
            if (response[response.length - 1] != "}") {
                res = response.substr(0, response.length - 1)
            }
            console.log("resss", res)
            if (res == "round done") {
                offset++;
                syncCount++;
                console.log("round done", syncCount);
                jQuery('.sync-article-num').text(syncCount)
                foundArticle = true;
                coreSyncProcess(offset, articlesTotalNum);
            } else if (res == "sync finished") {
                jQuery(".sync-message").hide();
                jQuery('.sync-message--fail').hide();
                if (foundArticle) {
                    jQuery(".sync-message--finish").fadeIn();
                    console.log("sync finished");
                } else {
                    articleNotFound();
                }
            } else if (res == "not found") {
                articleNotFound();
            } else if (res == "keep seeking") {
                offset++;
                console.log("keep seeking offset", offset);
                console.log("keep seeking offset", articlesTotalNum);
                if (offset < articlesTotalNum)
                    coreSyncProcess(offset, articlesTotalNum);
                console.log("keep seeking");
            } else {
                jQuery('.sync-message').hide();
                jQuery('.sync-message--fail').fadeIn();
                console.log("errorrrr");
            }
            console.log('coreSyncProcess done')
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // show 503
            jQuery('.sync-message').hide();
            jQuery('.sync-message--fail').fadeIn();
            console.log(errorThrown)
        });
    }
</script>