(() => {
    const txpattr = '(?<d>"+)(?:[^"]|\\k<d>{2})*\\k<d>|(?<s>\'+)(?:[^\']|\\k<s>{2})*\\k<s>|[^\\s\'"\\/>]+';
    const txptail = `(?:\\[-?\\d+\\])?(?:\\s+\\$?[\\w\\-\\x80-\\xffff]+(?:\\s*=\\s*(?:${txpattr}))?)*\\s*\\/?(?<!\\-\\-)`;
    const txptag = `<\\/?(?:txp|[a-z]+:):[\\w\\-\\x80-\\xffff]+${txptail}\\>`;
    Prism.languages.markup.tag.addInlined('txp:php', 'php');
    Prism.languages.markup['txp:php'].pattern = RegExp(`(<txp:php${txptail}\\>)(?:<!\\[CDATA\\[(?:[^\\]]|\\](?!\\]>))*\\]\\]>|(?!<!\\[CDATA\\[)[\\s\\S])*?(?=<\\/txp:php>)`);
    Prism.languages.markup.tag.inside['attr-value'].inside['txp:php'] = Prism.languages.markup['txp:php'];
    
    const txp = structuredClone(Prism.languages.markup.tag);
    txp.pattern = RegExp(`${txptag}`);
    txp.inside['attr-value'].pattern = RegExp(`=\\s*(?:${txpattr})`);
    txp.inside['attr-value'].inside.punctuation[1].pattern = /^(\s*)(?:'+(?!')|"+(?!"))|(?:(?<!')'+|(?<!")"+)$/;
    txp.inside['attr-value'].inside.txp = txp;
    Prism.languages.markup.tag.inside['attr-value'].pattern = RegExp(`=\\s*(?:(?<x>["\'])(?:(?:${txptag})|(?!\\k<x>).)*\\k<x>)`, 's');
    Prism.languages.markup.tag.pattern = RegExp(`<\\/?(?!\\d)[^\\s>\\/=$<%]+(?:\\s+[^\\s>\\/=]+(?:\\s*=\\s*(?:(?<x>["\'])(?:(?:${txptag})|(?!\\k<x>).)*\\k<x>|[^\\s\'"\\/>]+(?=[\s>])))?)*\\s*\\/?\\>`, 's');
    Prism.languages.markup.tag.inside['attr-value'].inside.txp = txp;
    Prism.languages.insertBefore('markup', 'tag', {txp: txp});
})();