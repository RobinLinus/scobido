<?
function isTorRequest() {
  $reverse_client_ip = implode('.', array_reverse(explode('.', $_SERVER['REMOTE_ADDR'])));
  $reverse_server_ip = implode('.', array_reverse(explode('.', $_SERVER['SERVER_ADDR'])));
  $hostname = $reverse_client_ip . "." . $_SERVER['SERVER_PORT'] . "." . $reverse_server_ip . ".ip-port.exitlist.torproject.org";
  return gethostbyname($hostname) == "127.0.0.2";
} 
?>