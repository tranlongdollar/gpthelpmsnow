<?php
// Tạo rawlink-top.txt: chỉ các link cần thiết cho dev
parse_str(implode('&', array_slice($argv, 1)), $a);
$owner=$a['owner']??''; $repo=$a['repo']??''; $ref=$a['ref']??'main';
$api="https://api.github.com/repos/$owner/$repo/git/trees/".rawurlencode($ref)."?recursive=1";
$h=['Accept: application/vnd.github+json','User-Agent: rawlinks-generator'];
$ch=curl_init($api); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>$h]); $resp=curl_exec($ch); curl_close($ch);
$tree=json_decode($resp,true)['tree']??[];
$raw="https://raw.githubusercontent.com/$owner/$repo/refs/heads/$ref/";
$keep=[
  '~^mysql\.sql$~',
  '~^massagenow\.vn/app/.*\.php$~',
  '~^massagenow\.vn/public_html/index\.php$~',
  '~^massagenow\.vn/public_html/api/.*\.php$~',
  '~^massagenow\.vn/public_html/admin/.*\.php$~',
  '~^massagenow\.vn/views/.*\.php$~',
];
$lines=[];
foreach($tree as $n){
  if(($n['type']??'')!=='blob') continue; $p=$n['path'];
  foreach($keep as $rg){ if(preg_match($rg,$p)){ $lines[]=$raw.$p; break; } }
}
sort($lines, SORT_NATURAL);
file_put_contents('rawlink-top.txt', implode("\n",$lines)."\n");
