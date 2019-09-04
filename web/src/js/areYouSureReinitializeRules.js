
export default (function areYouSureReinitialize() {
    $(document).ready(function() {
        const formsToReinitializeArr = ['.zasoby_edit #general form']; //tablica formularzy do ponownego zainicjowania po załadowaniu strony

        for (let i = 0; i < formsToReinitializeArr.length; i++) {
            let formToReinitialize = $(formsToReinitializeArr[i]);

            if (formToReinitialize) {
                $(formToReinitialize).trigger('reinitialize.areYouSure');
                console.info('Are You Sure reintialized on form ', formToReinitialize[i]);
            } else {
                console.warn('There is no form to reinitialize');
            }
        }
    });
})();
