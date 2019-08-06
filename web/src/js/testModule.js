function smallText(txt) {
    return txt.toLowerCase();
}

function bigText(txt) {
    return txt.toUpperCase();
}

function mixText(txt) {
    return [...txt].map((el, i) => i%2 === 0 ? el.toLowerCase() : el.toUpperCase());
}

export { smallText, bigText, mixText }