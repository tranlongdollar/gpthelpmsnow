<?php
// Tạo rawlink.md đầy đủ
parse_str(implode('&', array_slice($argv, 1)), $a);
$owner=$a['owner']??''; $repo=$a['repo']??''; $ref=$a['ref']??'main';
$api="https://api.github.com/repos/$owner/$repo/git/trees/".rawurlencode($ref)."?recursive=1";
$h=['Accept: application/vnd.github+json','User-Agent: rawlinks-generator'];
$ch=curl_init($api); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>$h]); $resp=curl_exec($ch); $code=curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
if($code!==200||!$resp){fwrite(STDERR,"ERR trees"); exit(1);}
$tree=json_decode($resp,true)['tree']??[];
$commit="https://api.github.com/repos/$owner/$repo/commits/".rawurlencode($ref);
$ch=curl_init($commit); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>$h]); $cres=curl_exec($ch); curl_close($ch);
$sha=json_decode($cres,true)['sha']??'';
$rawB="https://raw.githubusercontent.com/$owner/$repo/refs/heads/$ref/"; $rawS=$sha?"https://raw.githubusercontent.com/$owner/$repo/$sha/":'';
$out="# RAW Links — $owner/$repo\n\nBranch/Ref: $ref".($sha?"  (pinned SHA: `$sha`)":"")."\n\n| Purpose | Repo path | RAW (branch) | RAW (pinned) |\n|---|---|---|---|\n";
foreach($tree as $n){
  if(($n['type']??'')!=='blob') continue; $p=$n['path'];
  $purpose='File';
  if(preg_match('~/public_html/index\.php$~',$p)) $purpose='Front controller';
  elseif(preg_match('~/public_html/admin/~',$p)) $purpose='Admin';
  elseif(preg_match('~/public_html/api/~',$p)) $purpose='API';
  elseif(preg_match('~/views/~',$p)) $purpose='View';
  elseif(preg_match('~/app/~',$p)) $purpose='App core';
  elseif(preg_match('~\.sql$~',$p)) $purpose='SQL';
  elseif(preg_match('~\.md$~i',$p)) $purpose='Doc';
  $out.="| $purpose | $p | ".$rawB.$p." | ".($sha?$rawS.$p:'-')." |\n";
}
file_put_contents('rawlink.md',$out);
