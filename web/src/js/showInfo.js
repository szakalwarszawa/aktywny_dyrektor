function showGreeting(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

function showSmallInfo(info) {
    console.info(
        `%c ${info} `,
        'color: #333; font-size: 10px;',
    );
}

function showBigInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 0px 0px 0 1px #7A7A7A; color: #333; font-size: 16px;',
    );
}

export { showGreeting, showSmallInfo, showBigInfo }
