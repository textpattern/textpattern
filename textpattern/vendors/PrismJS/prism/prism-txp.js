(()=>{
    const txptag = '<\\/?(?:txp|[a-z]+:):[\\w\\-\\x80-\\xffff]+(?:\\[-?\\d+\\])?(?:\\s+\\$?[\\w\\-\\x80-\\xffff]+(?:\\s*=\\s*(?:(?<d>"+)(?:[^"]|\\k<d>{2})*\\k<d>|(?<s>\'+)(?:[^\']|\\k<s>{2})*\\k<s>|[^\\s\'"\\/>]+))?)*\\s*\\/?(?<!\\-\\-)\\>';
    const txpattr = '(?<ad>"+)(?:[^"]|\\k<ad>{2})*\\k<ad>|(?<as>\'+)(?:[^\']|\\k<as>{2})*\\k<as>|[^\\s\'"\\/>]+';
    Prism.languages.markup.tag.addInlined('txp:php', 'php');
    Prism.languages.markup['txp:php'].pattern = RegExp(`(<txp:php\\b(?:\\[-?\\d+\\])?(?:\\s+\\$?[\\w\\-\\x80-\\xffff]+(?:\\s*=\\s*(?:${txpattr}))?)*\\s*\\/?(?<!\\-\\-)\\>)(?:<!\\[CDATA\\[(?:[^\\]]|\\](?!\\]>))*\\]\\]>|(?!<!\\[CDATA\\[)[\\s\\S])*?(?=<\\/txp:php>)`);
    Prism.languages.markup.tag.inside['attr-value'].inside['txp:php'] = Prism.languages.markup['txp:php'];
    
    const txp = structuredClone(Prism.languages.markup.tag);
    txp.pattern = RegExp(`${txptag}`);
    txp.inside['attr-value'].pattern = RegExp(`=\\s*(?:${txpattr})`);
    txp.inside['attr-value'].inside.txp = txp;
    Prism.languages.markup.tag.inside['attr-value'].pattern = RegExp(`=\\s*(?:"(?:(?:${txptag})|[^"])*"|\'(?:(?:${txptag})|[^\'])*\'|${txpattr})`);
    Prism.languages.markup.tag.pattern = RegExp(`<\\/?(?!\\d)[^\\s>\\/=$<%]+(?:\\s+[^\\s>\\/=]+(?:\\s*=\\s*(?:"(?:(?:${txptag})|[^"]|"{2})*"|\'(?:(?:${txptag})|[^\']|\'{2})*\'|[^\\s\'"\\/>]+(?=[\s>])))?)*\\s*\\/?\\>`);
    Prism.languages.markup.tag.inside['attr-value'].inside.txp = txp;
    Prism.languages.insertBefore('markup', 'tag', {txp: txp});
})()