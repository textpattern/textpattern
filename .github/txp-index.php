<?php
$gitview = 'https://github.com/textpattern/textpattern/blob';
$outdir = '.github/index';

    @mkdir($outdir);
    $branch = index_get_branch();

    index_code('callback_event');
    index_code('callback_event_ref');
    index_code('pluggable_ui');

    $files_php = array_merge(
        glob('*\.php'),
        glob_recursive('textpattern/*\.php'),
        glob_recursive('rpc/*\.php')
    );

    // gtxt
    $gtxt_ok = index_code_gtxt($files_php);
    index_file_save('gtxt_parse_ok', array_keys($gtxt_ok));
    index_file_save('gtxt_parse_ok_count', print_r($gtxt_ok, true));

    $lang_gtxt = lang_gtxt('textpattern/lang/en.ini');
    foreach (array_keys($gtxt_ok) as $key) {
        if (isset($lang_gtxt[$key])) {
            unset($lang_gtxt[$key]);
            unset($gtxt_ok[$key]);
        }
    }

    $lang_debug = parse_ini_file('textpattern/mode.ini');
    foreach (array_keys($lang_debug) as $key) {
        unset($gtxt_ok[$key]);
    }
    index_file_save('gtxt_parse_new_string', print_r($gtxt_ok, true));

    // Unset prefs from lang
    $lang_prefs = json_decode(file_get_contents('textpattern/vendors/Textpattern/DB/Data/core.prefs'), true);
    foreach ($lang_prefs as $key => $d) {
        if ($d['type'] == 0) {
            unset($lang_gtxt[$key]);
        }
    }

    // Unset some strings
    foreach (array_keys($lang_gtxt) as $key) {
        if (preg_match('%^(tag_|units|tab_|view_)%', $key)
            || preg_match('%^(article|file|image|link)_category_%', $key)
            || preg_match('%(categories_deleted|_context|_hed)$%', $key)
        ) {
            unset($lang_gtxt[$key]);
        }
    }
    ksort($lang_gtxt);

    // Use this file safety, not all gTxt strings can be parsed.
    // For example not parsed attribute 'label' in function inputLabel(),
    // function wrapRegion()
    index_file_save('gtxt_lang_not_used', array_keys($lang_gtxt));


exit;

function lang_gtxt($filename)
{
    $txt = file_get_contents($filename);
    if (preg_match_all('%^(\w+)\s+=>\s+(.*)$%m', $txt, $mm)) {
        return array_flip($mm[1]);
    }
    return array();
}


function index_code_gtxt($files)
{
    $gtxt_ok = array();
    $gtxt_bad = array();

    foreach ($files as $file) {
        $txt = file_get_contents($file);
        if (preg_match_all('%gTxt\((?:\s+)?(.*?)[\)\,]%s', $txt, $mm)) {
            foreach ($mm[1] as $m) {
                if (preg_match('%^[\'\"]([\w\-]+)[\'\"]$%', trim($m), $nn)) {
                    @$gtxt_ok[$nn[1]]++;
                } elseif (preg_match("% \? '([\w\-]+)' : '([\w\-]+)'%", trim($m), $nn)) {
                    @$gtxt_ok[$nn[1]]++;
                    @$gtxt_ok[$nn[2]]++;
                } else {
                    $gtxt_bad[$m] = $file;
                }
            }
        }
    }

    ksort($gtxt_ok);
    ksort($gtxt_bad);
    index_file_save('gtxt_parse_bad', print_r($gtxt_bad, true));
    return $gtxt_ok;
}


function index_code($search)
{
    global $gitview, $branch;
    $out = array();
    $out2 = array();
    $txt = shell_exec('grep --exclude-dir={.github,sites} --include=\*.php -rn . -e "'.escapeshellcmd($search).'("');

    if (preg_match_all('%^\./(.*?):(.*?):(?:\s+)?(.*?'.preg_quote($search).'\((.*))$%ium', $txt, $mm)) {
        foreach ($mm[3] as $key=>$d) {
            if (! preg_match('%^(\*|function)%', trim($d))) {
                $d = trim(preg_replace('%(<\?php|\?\>)%', '', $d));
                $k = preg_replace('%[\,\)].*$%', '', $mm[4][$key]);
                $out["{$k}_{$key}"] = "<tr><td>{$k}</td><td>{$d}</td><td><a href='{$gitview}/{$branch}/{$mm[1][$key]}#L{$mm[2][$key]}'>{$mm[1][$key]}</a></td></tr>";
            }
        }
        ksort($out);
        index_file_save_html($search, '<table class="idx">'.join("\n", $out)."\n".'</table>');
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
    if (is_array($data)) {
        $data = join("\n", $data)."\n";
    }
    file_put_contents("{$outdir}/{$branch}__{$name}.txt", $data);
}

function index_file_save_html($name, $data)
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

function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}
