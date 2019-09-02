const formsToReinitializeArr = ['#general form']; //tablica formularzy do ponownego zainicjowania po załadowaniu strony

export default $(document).ready(function areYouSureReinitialize(formsToReinitializeArr) {
    debugger;
    for (let i = 0; i < formsToReinitializeArr.length; i++) {
        let formToReinitialize = $(formsToReinitializeArr[i]);
        console.log('areYouSureReinitialize() loop ' + i + ' form ', formToReinitialize);
        if (formToReinitialize) {
            $(formToReinitialize).trigger('reinitialize.areYouSure');
            console.info('Are You Sure reintialized');
        } else {
            console.warn('There is no form to reinitialize');
        }
    }
});
