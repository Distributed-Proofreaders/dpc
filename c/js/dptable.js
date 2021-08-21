/**
 * Javascript include file for DpTable.class.php
 * Included via require_once during class construction.
 * Note the only reason this is a separate file from DpTable is
 * that DpTable is included in dpinit before the <html> tags have
 * been emitted.  (The browsers don't actually care if you do that, but
 * I don't like the idea.)
 * Quite possibly DpTable shouldn't be included in dpinit, but I don't want
 * to try to clean up everything.
 *
 * This file is for runtime filtering of the table based on a table row
 * of class filters, normally the second row of the table.
 */
dptablefilter = {

    init: function()
    {
        var tables = document.getElementsByTagName('table');
        for (var i = 0; i < tables.length; i++) {
            var table = tables[i];
            if (table.className.search(/\bsortable\b/) != -1)
                dptablefilter.makeFilterable(table);
        }
    },

    /**
     * Hookup a change listener to each filter combobox.
     */
    makeFilterable: function(table)
    {
        var rows = table.rows;

        if (rows.length < 2)
            return;

        // Row 1 is filters

        // If it isn't of class filters, ignore this table.
        if (rows[1].className.search(/\bfilters\b/) == -1)
            return;
        var filterCells = rows[1].cells;

        // Yes, it is a filter row, add change listener to each select
        for (var col = 0; col < filterCells.length; col++) {
            var filterCell = filterCells[col];
            var sel = filterCell.getElementsByTagName("select");
            if (sel.length == 0)
                continue;
            // Found a filter
            sel = sel[0];
            sel.addEventListener("change", dptablefilter.comboClick);

            // Restore combo state
            dptablefilter.restoreState(sel);
        }

        // Row 0 is headers -- add click handler on each column to fix sort
        var headings = rows[0].cells;
        for (var col = 0; col < headings.length; col++) {
            var colCell = headings[col];
            colCell.addEventListener("click", dptablefilter.fixFilters);
        }

        // If one of the combos was set by us, filter now.
        dptablefilter.filter(table);
    },

    storageKeyName(select)
    {
        return "dptable_" + select.id;
    },

    restoreState: function(select)
    {
        var key = dptablefilter.storageKeyName(select);
        var selectedOption = localStorage.getItem(key);
        var options = select.options;
        var match = false;
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (opt.value == selectedOption) {
                opt.selected = true;
                match = true;
            } else
                opt.selected = false;
        }
        if (!match)
            // No longer a match? Select the first option, -all-
            options[0].selected = true;
    },

    saveState: function(select)
    {
        var key = dptablefilter.storageKeyName(select);
        var opt = select.options[select.selectedIndex];
        localStorage.setItem(key, opt.value);
    },

    /**
     * Interaction with sorting (sorttable.js), without changing that class.
     * The correct fix is to make sorttable.js work with multiple header rows,
     * but right now it just decides a table with multiple header rows isn't
     * sortable.
     * Thus, we had to make our filter row not part of the header.
     * The problem with that is then it sorts!
     * This hack function runs on the click bubble up event,
     * after the sort has happened.
     * It just finds our filter row, and moves it back up to where it
     * is supposed to be!
     */
    fixFilters: function()
    {
        var table = dptablefilter.getTable(this);
        var rows, sortrow;

        rows = table.rows;
        sortrow = 1;    // Do nothing if no filter row!
        for (var row = 0; row < rows.length; row++) {
            var tr = rows[row];
            if (tr.className.search(/\bfilters\b/) != -1) {
                sortrow = row;
                break;
            }
        }
        if (sortrow != 1) {
            rows[1].parentNode.insertBefore(rows[sortrow], rows[1]);
        }
    },

    getTable: function(cell)
    {
        return cell.closest(".sortable");
    },

    comboClick: function()
    {
        dptablefilter.filter(dptablefilter.getTable(this));
    },

    /**
     * The actual function which does the filtering.
     * It is an AND of all the filters.
     * The first filter in each combo box is known to be the "all",
     * i.e. don't filter.
     */
    filter: function(table)
    {
        var rows = table.rows;
        var filterCells = rows[1].cells;
        // Row 0 is headers
        // Row 1 is filters
        var count = 0;
        for (var row = 2; row < rows.length; row++) {
            var rowCells = rows[row].cells;
            var match = true;
            for (var col = 0; col < filterCells.length; col++) {
                var filterCell = filterCells[col];
                var sel = filterCell.getElementsByTagName("select");
                if (sel.length == 0)
                    continue;
                // Found a filter
                sel = sel[0];
                dptablefilter.saveState(sel);
                if (sel.selectedIndex == 0) {
                    // All selected
                    continue;
                }
                var v1 = sel.value;
                if (col >= rowCells.length) {
                    // Past the end of this row
                    continue;
                }
                var td = rowCells[col];
                var v2 = td.textContent;
                v2 = v2.trim();
                //console.log("Comparing " + v1 + " to " + v2 + ": " + (v1 != v2));
                if (v1 != v2) {
                    match = false;
                    break;
                }
            }
            if (match) {
                rows[row].style.display = "";
                count++;
            } else
                rows[row].style.display = "none";
        }
        dptablefilter.updateCount(count, rows.length-2);
    },

    updateCount: function(count, total)
    {
        dptablefilter.setCounts('count', count);
        dptablefilter.setCounts('total', total);
    },

    setCounts: function(name, n)
    {
        var spans = document.getElementsByClassName('dptablefilter_'+name);

        for (var i = 0; i < spans.length; i++)
            spans[i].innerText = n + "";
    }
}

window.addEventListener("load", dptablefilter.init, false);

/*
 * vim: sw=4 ts=4 expandtab
 */
