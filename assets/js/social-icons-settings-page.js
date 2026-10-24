jQuery(function ($) {
    var $inner = $(".wpzoom-social-icons-settings-inner");
    // A link to a tab (…#preferred-source) wins over the last tab viewed.
    var hashTab = location.hash ? $inner.find(".filter-links a").index($inner.find('.filter-links a[href="' + location.hash + '"]')) : -1;

    // Remember it too, so saving the form comes back to the same tab.
    if (hashTab > -1) {
        localStorage.setItem("socialIconsCurrentSettingsTab", hashTab);
    }

    // Tabs with nothing to save (Custom Icons) hide the Save button.
    var toggleSubmit = function (panel) {
        $inner.find("form > .submit").toggle(!$(panel).data("hide-submit"));
    };

    $inner.tabs({
        classes: {"ui-tabs-active": "current"},
        active: hashTab > -1 ? hashTab : localStorage.getItem("socialIconsCurrentSettingsTab"),
        create: function (event, ui) {
            toggleSubmit(ui.panel);
        },
        activate: function (event, ui) {
            localStorage.setItem("socialIconsCurrentSettingsTab", $(this).tabs('option', 'active'));
            toggleSubmit(ui.newPanel);
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
