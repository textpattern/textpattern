Prism.languages.insertBefore('markup', 'tag', {txp: {...Prism.languages.markup.tag}});
Prism.languages.markup.txp.pattern=/<\/?(?:txp|[a-z]+:):[\w\-\x80-\xffff]+(?:\[-?\d+\])?(?:\s+\$?[\w\-\x80-\xffff]+(?:\s*=\s*(?:(?<d>"+)(?:[^"]|\k<d>{2})*\k<d>|(?<s>\'+)(?:[^\']|\k<s>{2})*\k<s>|[^\s\'"\/>]+))?)*\s*\/?(?<!\-\-)\>/s;
Prism.languages.markup.txp.inside['attr-value'].pattern=/=\s*(?:(?<d>"+)(?:[^"]|\k<d>{2})*\k<d>|(?<s>\'+)(?:[^\']|\k<s>{2})*\k<s>|[^\s\'"\/>]+)/s;
Prism.languages.markup.txp.inside['attr-value'].greedy = false;
Prism.languages.markup.txp.inside['attr-value'].inside.txp = Prism.languages.markup.txp;
Prism.languages.markup.txp.addInlined = Prism.languages.markup.tag.addInlined;
Prism.languages.markup.txp.addInlined('txp:php', 'php');
Prism.languages.markup['txp:php'].pattern=/(<txp:php(?:\[-?\d+\])?(?:\s+\$?[\w\-\x80-\xffff]+(?:\s*=\s*(?:(?<d>"+)(?:[^"]|\k<d>{2})*\k<d>|(?<s>\'+)(?:[^\']|\k<s>{2})*\k<s>|[^\s\'"\/>]+))?)*\s*\/?(?<!\-\-)\>)(?:<!\[CDATA\[(?:[^\]]|\](?!\]>))*\]\]>|(?!<!\[CDATA\[)[\s\S])*?(?=<\/txp:php>)/
