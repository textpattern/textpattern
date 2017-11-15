<?php
$gitview = 'https://github.com/textpattern/textpattern/blob';
$outdir = '.github/index';

    @mkdir($outdir);
    $branch = index_get_branch();

    index_code('callback_event');
    index_code('callback_event_ref');

exit;

function index_code($search)
{
    global $gitview, $branch, $outdir;
    $out = array();
    $out2 = array();
    $txt = shell_exec('grep --exclude-dir=.github --include=\*.php -rn . -e "'.escapeshellcmd($search).'("');

    if (preg_match_all('%^\./(.*?):(.*?):(?:\s+)?(.*?'.preg_quote($search).'\((.*))$%ium', $txt, $mm)) {
        foreach ($mm[3] as $key=>$d) {
            if (! preg_match('%^(\*|function)%', trim($d))) {
                $d = trim(preg_replace('%(<\?php|\?\>)%', '', $d));
                $k = preg_replace('%[\,\)].*$%', '', $mm[4][$key]);
                $out["{$k}_{$key}"] = "<tr><td>{$k}</td><td>{$d}</td><td><a href='{$gitview}/{$branch}/{$mm[1][$key]}#L{$mm[2][$key]}'>{$mm[1][$key]}</a></td></tr>";
            }
        }
        ksort($out);
        index_file_save($search, '<table class="idx">'.join("\n", $out)."\n".'</table>');
    }
}


function index_get_branch()
{
    $txt = @file_get_contents('.git/HEAD');

    if (preg_match('%refs/heads/(.*)$%m', $txt, $mm)) {
        return $mm[1];
    }

    return '';
}

function index_file_save($name, $data)
{
    global $branch, $outdir;
$head =<<<EOF
<!DOCTYPE html><html lang="en-gb" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>{$branch} :: {$name}</title>
<style>
body { background-color: #fafafa; }
.idx tr:nth-child(odd) { background-color: #eee; }
.idx tr:hover { background-color: #ccc; }
</style>
</head>
<body>
EOF;
    file_put_contents("{$outdir}/{$branch}__{$name}.html", $head.$data.'</body></html>');
}
