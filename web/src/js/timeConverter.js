export default function timeConverter(UNIX_timestamp) {
    var a = new Date(UNIX_timestamp * 1000),
        months = [ '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' ],
        year = a.getFullYear(),
        month = months[a.getMonth()],
        date = a.getDate();
    // --- gdyby trzeba było pokazać datę wraz z godziną odkomentuj 4 poniższe linie ---
    // var hour = a.getHours();
    // var min = a.getMinutes() < 10 ? '0' + a.getMinutes() : a.getMinutes();
    // var sec = a.getSeconds() < 10 ? '0' + a.getSeconds() : a.getSeconds();
    // var time = date + ' ' + month + ' ' + year + ' ' + hour + ':' + min + ':' + sec ;
    // var time = date + ' ' + month + ' ' + year;
    var time = year + '-' + month + '-' + date;
    return time;
}
