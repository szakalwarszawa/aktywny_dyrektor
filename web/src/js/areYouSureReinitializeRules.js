export default $(document).ready(function areYouSureReinitialize() {
    var generalForm = $('#general form');
    console.log('areYouSureReinitialize() general form ', generalForm[0]);
    if (generalForm) {
        $(generalForm[0]).trigger('reinitialize.areYouSure');
        console.info('Are You Sure reintialized');
    } else {
        console.warn('Are You Sure methods are not active here');
    }
});
