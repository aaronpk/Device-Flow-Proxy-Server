<?php

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
}

function base64_urlencode($string) {
  return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
}

function random_alpha_string($len) {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  $str = '';
  for($i=0; $i<$len; $i++)
    $str .= substr($chars, random_int(0, strlen($chars)-1), 1);
  return $str;
}

class Cache {
  private static $redis;

  public static function connect($host=false) {
    if(!isset(self::$redis)) {
      if($host) {
        self::$redis = new Predis\Client($host);
      } else {
        self::$redis = new Predis\Client();
      }
    }
  }

  public static function set($key, $value, $exp=600) {
    self::connect();
    self::$redis->setex($key, $exp, json_encode($value));
  }

  public static function get($key) {
    self::connect();
    $data = self::$redis->get($key);
    if($data) {
      return json_decode($data);
    } else {
      return null;
    }
  }

  public static function add($key, $value, $exp=600) {
    self::connect();
    self::$redis->setex($key, $exp, json_encode($value));
  }

  public static function expire($key, $exp) {
    self::connect();
    self::$redis->expire($key, $exp);
  }

  public static function incr($key, $value=1) {
    self::connect();
    self::$redis->incrby($key, $value);
  }

  public static function delete($key) {
    self::connect();
    self::$redis->del($key);
  }
}
