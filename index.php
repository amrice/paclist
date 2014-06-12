<?php


function startsWith($str, $sub)
{
    return !strncmp($str, $sub, strlen($sub));
}

function endsWith($str, $sub)
{
    $length = strlen($sub);
    if ($length == 0) {
        return true;
    }

    return (substr($str, -$length) === $sub);
}

function replaceToNomal($str)
{
    $str = preg_replace('/([\\\+\|\{\}\[\]\(\)\^\$\.\#])/', '\\\${1}', $str);
    $str = str_replace('*', '.*', $str);
    $str = str_replace('?', '.', $str);
    return $str;
}

function toJSArray($array)
{
    $r = array();
    array_push($r, "[");
    $size = count($array);
    for ($i = 0; $i < $size; $i++) {
        array_push($r, "    decode('" . base64_encode($array[$i]) . "')" . ($i == $size - 1 ? "" : ","));
    }
    array_push($r, "]");
    return implode("\n", $r);
}

function toPac($list, $proxy)
{
    $directNomalRules = array();
    $directRegexRules = array();
    $proxyRegexRules = array();
    $proxyNomalRules = array();

    for ($i = 0; $i < count($list); $i++) {
        $rule = trim($list[$i]);
        $isRegex = true;
        $isProxy = true;

        if (strlen($rule) == 0 || startsWith($rule, "!") || startsWith($rule, "[")) {

            continue;
        }

        if (startsWith($rule, "@@")) {
            $isProxy = false;
            $rule = substr($rule, 2);
        }

        if (startsWith($rule, "/") and endsWith($rule, "/")) {
            $rule = substr(substr($rule, 1), 0, count($rule) - 2);
        } else if (startsWith($rule, "||")) {
            $rule = substr($rule, 2);
            $rule = replaceToNomal($rule);
            $rule = '^[\\w\\-]+:\\/+(?!\\/)(?:[^\\/]+\\.)?' . $rule;
        } else if (startsWith($rule, "|") or endsWith($rule, "|")) {

            $rule = replaceToNomal($rule);

            if (startsWith($rule, "\\|")) $rule = "^" . substr($rule, 2);
            if (endsWith($rule, "\\|")) $rule = substr($rule, 0, -2) . "$";
        } else {
            $isRegex = false;
            if (startsWith($rule, "*") == false) $rule = "*$rule";
            if (endsWith($rule, "*") == false) $rule = "$rule*";
        }

        if ($isProxy) {
            if ($isRegex) {
                array_push($proxyRegexRules, $rule);
            } else {
                array_push($proxyNomalRules, $rule);
            }

        } else {
            if ($isRegex) {
                array_push($directRegexRules, $rule);
            } else {
                array_push($directNomalRules, $rule);
            }
        }


    }


    $directNomalRules = toJSArray($directNomalRules);
    $directRegexRules = toJSArray($directRegexRules);
    $proxyRegexRules = toJSArray($proxyRegexRules);
    $proxyNomalRules = toJSArray($proxyNomalRules);

    echo file_get_contents("Base64.js");
    echo <<<JS
function decode(url){
      return Base64.decode(url);
}
var directNomalRules = $directNomalRules;
var directRegexRules = $directRegexRules;
var proxyRegexRules = $proxyRegexRules;
      
var proxyNomalRules = $proxyNomalRules;
   var regExpMatch = function(url, pattern) {
      try {
         return new RegExp(pattern).test(url);
      } catch(ex) {
         return false;
      }
   };
var FindProxyForURL = function(url,host){
   var p = "$proxy";
   var d = "DIRECT";
   for(var i in directNomalRules ){
      if(shExpMatch(url, directNomalRules[i])) return d;
   }
   for(var i in directRegexRules ){
      if(regExpMatch(url, directRegexRules[i])) return d;
   }
   for(var i in proxyNomalRules ){
      if(shExpMatch(url, proxyNomalRules[i])) return p;
   }
   for(var i in proxyRegexRules ){
      if(regExpMatch(url, proxyRegexRules[i])) return p;
   }
   return d;
}
JS;

}

$gfw_list = file_get_contents("gfw.user.rule");
if ( @$_REQUEST['gfw'] != "0" ){
    $gfw_url = "http://autoproxy-gfwlist.googlecode.com/svn/trunk/gfwlist.txt";
    $gfw_list_b64 = file_get_contents($gfw_url) or die("get $gfw_url error!");
    $gfw_list = $gfw_list."\n" . base64_decode($gfw_list_b64);
}

$o = @$_REQUEST["o"];
$p = @$_REQUEST["p"];
$pt = @$_REQUEST["pt"];

if ($p == null) $p = "127.0.0.1:7777";
if ($pt == null) $pt = "SOCKS5";

if ($o == "html") echo "<pre>";

$f = @$_REQUEST["f"];

if ($f == null)
    $f = "test";
if ($f == "write_user_rule") {
    if (@$_REQUEST['gfw_user_rule'] != null)
        file_put_contents("gfw.user.rule", @$_REQUEST['gfw_user_rule']);
    header("location: /");
    exit(0);
}
if ($f == "decode")
    echo $gfw_list;
else if ($f == "pac")
    echo toPac(explode("\n", $gfw_list), "$pt $p");
else if ($f == "test") {

    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>YuKunYi Proxy Pac Manager</title>
        <script src="/?f=pac&p=test.proxy.com&pt=socks" type="text/javascript"></script>

        <script type="text/javascript">
            var matchPattern = null;
            function shExpMatch(url, pattern) {
                var pCharCode;
                var isAggressive = false;
                var pIndex;
                var urlIndex = 0;
                var lastIndex;
                var patternLength = pattern.length;
                var urlLength = url.length;
                for (pIndex = 0; pIndex < patternLength; pIndex += 1) {
                    pCharCode = pattern.charCodeAt(pIndex); // use charCodeAt for performance, see http://jsperf.com/charat-charcodeat-brackets
                    if (pCharCode === 63) { // use if instead of switch for performance, see http://jsperf.com/switch-if
                        if (isAggressive) {
                            urlIndex += 1;
                        }
                        isAggressive = false;
                        urlIndex += 1;
                    }
                    else if (pCharCode === 42) {
                        if (pIndex === patternLength - 1) {
                            if (urlIndex <= urlLength) {
                                matchPattern = pattern;
                                return true;
                            }
                            return false;
                        }
                        else {
                            isAggressive = true;
                        }
                    }
                    else {
                        if (isAggressive) {
                            lastIndex = urlIndex;
                            urlIndex = url.indexOf(String.fromCharCode(pCharCode), lastIndex + 1);
                            if (urlIndex < 0) {
                                if (url.charCodeAt(lastIndex) !== pCharCode) {
                                    return false;
                                }
                                urlIndex = lastIndex;
                            }
                            isAggressive = false;
                        }
                        else {
                            if (urlIndex >= urlLength || url.charCodeAt(urlIndex) !== pCharCode) {
                                return false;
                            }
                        }
                        urlIndex += 1;
                    }
                }
                if (urlIndex === urlLength) {
                    matchPattern = pattern;
                    return true;
                }
                return false;
            }
            function test() {
                var url = document.getElementById("url").value;
                if (url.indexOf("://") == -1) url = "http://" + url;
                var resultDiv = document.getElementById("result");
                matchPattern = null;
                resultDiv.innerHTML = "FindProxyForURL return is : " + FindProxyForURL(url) + " , " + (matchPattern != null ? " match " + matchPattern : " not match any pattern.");
            }


            regExpMatch = function (url, pattern) {
                try {
                    if (new RegExp(pattern).test(url)) {
                        matchPattern = pattern;
                        return true;
                    }
                    return false;
                } catch (ex) {
                    return false;
                }
            };
        </script>

    </head>
    <body>
    <script type="text/javascript">
        document.write("directNomalRules has " + directNomalRules.length + " items.<br/>");
        document.write("directRegexRules has " + directRegexRules.length + " items.<br/>");
        document.write("proxyRegexRules has " + proxyRegexRules.length + " items.<br/>");
        document.write("proxyNomalRules has " + proxyNomalRules.length + " items.<br/>");
    </script>
    URL:<textarea type="text" id="url" style="width:800px;"></textarea><a href="javascript:test()">Test</a>

    <div id="result"></div>
    <br/>
    <br/>
    <br/>

    <div>
        <form action="/?f=write_user_rule" method="post">
            UserRule:<textarea name="gfw_user_rule"
                               style="width:800px;height:500px;"><?php echo file_get_contents("gfw.user.rule") ?></textarea>
            <br/>
            <input type="submit"/>
        </form>
    </div>
    </body>
    </html>


<?php

}


if ($o == "html") echo "</pre>";