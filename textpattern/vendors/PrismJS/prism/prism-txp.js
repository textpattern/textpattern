Prism.languages.markup = {txp: {...Prism.languages.markup.tag}, ...Prism.languages.markup};
Prism.languages.markup.txp.pattern=/<\/?(?:txp|[a-z]+:):[\w\-\x80-\xffff]+(?:\[-?\d+\])?(?:\s+\$?[\w\-\x80-\xffff]+(?:\s*=\s*(?:(?<d>"+)(?:[^"]|\k<d>")*\k<d>|(?<s>\'+)(?:[^\']|\k<s>\')*\k<s>|[^\s\'"\/>]+))?)*\s*\/?(?<!\-\-)\>/s;
Prism.languages.markup.txp.inside['attr-value'].pattern=/=\s*(?:(?<d>"+)(?:[^"]|\k<d>")*\k<d>|(?<s>\'+)(?:[^\']|\k<s>\')*\k<s>|[^\s\'"\/>]+)/s;
Prism.languages.markup.txp.inside['attr-value'].greedy = false;
Prism.languages.markup.txp.inside['attr-value'].inside.txp = Prism.languages.markup.txp;
