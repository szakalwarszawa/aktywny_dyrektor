import '../scss/app.scss';

function showInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

showInfo('Aktywny Dyrektor v2.0 beta');
