jQuery(function ($) {
    var $inner = $(".wpzoom-social-icons-settings-inner");
    var $links = $inner.find(".filter-links a");
    var $panels = $inner.find("form > .tab");

    // A link to a tab (…#share-buttons-tab) or to a section inside one
    // (…#preferred-source, #font-styles) wins over the last tab viewed.
    var hashTab = -1;
    var $section = $();
    if (location.hash && /^#[\w-]+$/.test(location.hash)) {
        hashTab = $links.index($links.filter('[href="' + location.hash + '"]'));
        if (hashTab < 0) {
            $section = $inner.find(location.hash);
            hashTab = $section.length ? $panels.index($section.closest(".tab")) : -1;
        }
    }

    // Remember it too, so saving the form comes back to the same tab.
    if (hashTab > -1) {
        try { localStorage.setItem("socialIconsCurrentSettingsTab", hashTab); } catch (e) {}
    }

    var stored = null;
    try { stored = parseInt(localStorage.getItem("socialIconsCurrentSettingsTab"), 10); } catch (e) {}
    // Tabs were regrouped by module: an index from before may be out of range.
    if (isNaN(stored) || stored < 0 || stored >= $links.length) {
        stored = 0;
    }

    // The dashboard sidebar's Settings items open a tab, or a section in one
    // (Google Preferred Sources, Pinterest). The open one is highlighted.
    var $sideTabs = $(".wpzc-sidebar [data-wpzc-tab]");
    var currentSection = $section.length ? $section.attr("id") : "";
    var markSidebar = function () {
        var id = $panels.eq($inner.tabs("option", "active")).attr("id");
        var $match = currentSection ? $sideTabs.filter('[data-wpzc-section="' + currentSection + '"]') : $();
        if (!$match.length) {
            $match = $sideTabs.filter('[data-wpzc-tab="' + id + '"]').first();
        }
        $sideTabs.removeClass("is-current").removeAttr("aria-current");
        $match.addClass("is-current").attr("aria-current", "page");
        // Panels that only explain or upsell have nothing to save.
        $inner.find("form > .submit").toggle($panels.eq($inner.tabs("option", "active")).find(":input[name]").length > 0);
    };

    $inner.tabs({
        classes: {"ui-tabs-active": "current"},
        active: hashTab > -1 ? hashTab : stored,
        create: function () {
            // Runs before the widget is ready to report its active tab.
            setTimeout(markSidebar, 0);
        },
        activate: function () {
            try { localStorage.setItem("socialIconsCurrentSettingsTab", $(this).tabs("option", "active")); } catch (e) {}
            currentSection = "";
            markSidebar();
        }
    });

    if ($section.length) {
        $section[0].scrollIntoView({block: "start"});
    }

    // Open a tab, or the tab holding a section, from a #hash.
    var openHash = function (hash) {
        if (!hash || !/^#[\w-]+$/.test(hash)) {
            return;
        }
        var index = $panels.index($panels.filter(hash));
        var $target = $();
        if (index < 0) {
            $target = $inner.find(hash);
            index = $target.length ? $panels.index($target.closest(".tab")) : -1;
        }
        if (index > -1) {
            currentSection = $target.length ? $target.attr("id") : "";
            $inner.tabs("option", "active", index);
            markSidebar();
            if ($target.length) {
                $target[0].scrollIntoView({block: "start"});
            } else {
                window.scrollTo(0, 0);
            }
        }
    };

    $sideTabs.on("click", function (event) {
        event.preventDefault();
        var hash = "#" + ($(this).data("wpzc-section") || $(this).data("wpzc-tab"));
        if (window.history && history.replaceState) {
            history.replaceState(null, "", hash);
        }
        openHash(hash);
    });

    $(window).on("hashchange", function () {
        openHash(location.hash);
    });

    // Copy a shortcode (Saved icon sets).
    $(document).on("click", ".wpzc-copy", function () {
        var $button = $(this);
        var text = String($button.data("copy") || "");
        var done = function () {
            var label = $button.text();
            $button.text($button.data("done")).prop("disabled", true);
            setTimeout(function () { $button.text(label).prop("disabled", false); }, 1500);
        };
        var select = function () {
            $button.siblings("input").trigger("focus").trigger("select");
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, select);
        } else {
            select();
        }
    });

    // Google Preferred Sources: update the preview as the options change.
    var $preview = $(".wpzoom-ps-preview");
    if ($preview.length) {
        var field = function (key) {
            return $('[name="wpzoom_preferred_source[' + key + ']"]');
        };
        var refresh = function () {
            var $button = $preview.find(".wpzoom-preferred-source");
            var align = field("align").filter(":checked").val();
            $button.attr("class", [
                "wpzoom-preferred-source",
                "is-theme-" + field("theme").filter(":checked").val(),
                "is-size-" + field("size").filter(":checked").val(),
                align ? "has-text-align-" + align : ""
            ].join(" ").trim());
            $button.find(".wpzoom-preferred-source__label").text($.trim(field("label").val()) || $preview.data("default-label"));
        };
        $(".wpzoom-ps-card").on("change input", "input", refresh);
        $preview.on("click", "a", function (event) {
            event.preventDefault();
        });
    }
});
