import 'tree-multiselect';

export default $(function() {
    $('.multiselect-tree').treeMultiselect({
        hideSidePanel: false,
        sectionDelimiter: '@',
        collapsible: true,
        startCollapsed: true,
    });
})
