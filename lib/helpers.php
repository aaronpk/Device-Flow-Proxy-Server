<?php

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
}

class Cache {
  private static $mc;

  public static function mc($host=false) {
    if(!isset(self::$mc)) {
      if($host) {
        self::$mc = new Memcache;
        self::$mc->addServer($host);
      } else {
        self::$mc = new Memcache;
      }
    }
  }

  public static function set($key, $value, $exp=600) {
    self::mc();
    self::$mc->set($key, json_encode($value), 0, $exp);
  }

  public static function get($key) {
    self::mc();
    $data = self::$mc->get($key);
    if($data) {
      return json_decode($data);
    } else {
      return null;
    }
  }

  public static function delete($key) {
    self::mc();
    self::$mc->delete($key);
  }
}
