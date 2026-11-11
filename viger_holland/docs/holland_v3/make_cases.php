<?php
require __DIR__ . '/HollandEngine.php';
$seed = fn ($n) => json_decode(file_get_contents(__DIR__ . "/../../database/seeders/data/holland_v3/$n.json"), true);
$e = new HollandEngine($seed('settings'), $seed('riasec_pairs'), $seed('third_letters'), $seed('majors'), $seed('riasec_types'));
function mk(array $byGroup, array $ms = []): array {
    $out = []; $i = 0;
    for ($k = 0; $k < 6; $k++) foreach (str_split('RIASEC') as $g) {
        $out[] = ['code' => sprintf('%s%02d', $g, $k + 1), 'group' => $g, 'value' => $byGroup[$g][$k], 'ms' => $ms[$i] ?? 4000]; $i++;
    }
    return $out;
}
$cases = [
 'HS01_mau_cua_Thay_ISA' => mk(['R'=>[3,4,3,4,3,3],'I'=>[5,5,4,5,5,4],'A'=>[4,4,5,4,4,4],'S'=>[4,5,4,5,4,4],'E'=>[2,3,2,3,2,2],'C'=>[3,3,4,3,3,3]]),
 'HS02_IRC_chu3_vuot_4_2' => mk(['R'=>[5,4,5,4,5,4],'I'=>[5,5,5,4,5,4],'A'=>[3,3,3,3,3,3],'S'=>[2,3,2,3,2,3],'E'=>[2,2,2,2,3,2],'C'=>[4,5,4,4,5,4]]),
 'HS03_IRC_chu3_thieu_1_cau' => mk(['R'=>[5,4,5,4,5,4],'I'=>[5,5,5,4,5,4],'A'=>[3,3,3,3,3,3],'S'=>[2,3,2,3,2,3],'E'=>[2,2,2,2,3,2],'C'=>[4,5,4,null,5,4]]),
 'HS04_SIA_dao_thu_tu' => mk(['R'=>[3,4,3,4,3,3],'I'=>[4,5,4,5,4,4],'A'=>[4,4,4,4,4,4],'S'=>[5,5,4,5,5,4],'E'=>[2,3,2,3,2,2],'C'=>[3,3,4,3,3,3]]),
 'HS05_RS_doi_khang' => mk(['R'=>[4,5,4,4,5,4],'I'=>[3,2,3,2,3,2],'A'=>[3,2,3,2,3,2],'S'=>[4,5,4,4,5,4],'E'=>[3,2,3,2,3,2],'C'=>[3,2,3,2,3,2]]),
 'HS06_tat_ca_3' => mk(array_fill_keys(str_split('RIASEC'), [3,3,3,3,3,3])),
 'HS07_nhieu_khong_biet' => mk(['R'=>[null,null,null,4,4,4],'I'=>[5,5,4,5,5,4],'A'=>[null,null,4,4,4,4],'S'=>[4,5,4,5,4,4],'E'=>[2,null,2,3,2,2],'C'=>[3,3,null,3,3,3]]),
 'HS08_bam_voi' => mk(['R'=>[3,4,3,4,3,3],'I'=>[5,5,4,5,5,4],'A'=>[4,4,5,4,4,4],'S'=>[4,5,4,5,4,4],'E'=>[2,3,2,3,2,2],'C'=>[3,3,4,3,3,3]], array_merge(array_fill(0,18,4000), array_fill(0,6,700), array_fill(0,12,4000))),
];
$res = [];
foreach ($cases as $name => $ans) {
    $r = $e->score($ans);
    $res[$name] = ['answers' => $ans, 'expected' => [
        'means' => array_map(fn ($g) => $g['mean'], $r['groups']), 'order' => $r['order'], 'code2' => $r['code2'], 'code3' => $r['code3'],
        'third_detail' => $r['third_detail'], 'alternates' => $r['alternates'], 'relation_1_2' => $r['relations']['1-2']['relation'],
        'consistency' => $r['consistency'], 'strength' => $r['profile']['strength'], 'reliability' => $r['flags']['reliability'],
        'flags' => array_column($r['flags']['items'], 'code'),
        'majors_top5' => array_map(fn ($m) => [$m['id'], $m['name_de'], $m['code'], $m['c'], $m['label']], array_slice($r['majors'], 0, 5)),
    ]];
    printf("%-28s %s/%s third=%s alt=%s rel=%s %s rel=%s flags=%s\n   %s\n", $name, $r['code2'], $r['code3'], $r['third_detail'] ? 'Y' : 'N', implode(',', $r['alternates']),
        $r['relations']['1-2']['relation'], $r['consistency'], $r['flags']['reliability'], implode(',', array_column($r['flags']['items'], 'code')),
        implode(' | ', array_map(fn ($m) => "{$m['name_de']}({$m['code']},{$m['c']})", array_slice($r['majors'], 0, 5))));
}
file_put_contents(__DIR__ . '/cases.json', json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
