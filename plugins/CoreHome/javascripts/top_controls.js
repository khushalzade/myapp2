/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
function initTopControls() {
    var $topControlsContainer = $('.top_controls');

    var allRendered = true;

    if ($topControlsContainer.length) {
        $topControlsContainer.find('.piwikTopControl').each(function () {
            var $control = $(this);
            if ($control.css('display') == 'none') {
                return;
            }

            var width = $control.outerWidth(true);

            var isControlFullyRendered = width >= 30;
            if (!isControlFullyRendered) {
                allRendered = false;
            }
        });

        if (allRendered) {
            var alreadyRendered = $('.top_controls').css('visibility') === 'visible';

            // we make top controls visible only after all selectors are rendered
            $('.top_controls').css('visibility', 'visible');
            $('.top_controls').css('opacity', '1');

            if (!alreadyRendered) {
              window.CoreHome.Matomo.postEvent('Matomo.topControlsRendered');
            }
        }

    }
}

//Keyboard controls for Top Controls Calendar through tab and enter.
window.addEventListener('DOMContentLoaded', function () {
    $('.periodSelector').keydown(function(e){
        toggleCalendar(e);
    })

    blockPropegation();

    $('.periodSelector .form-radio').keydown(function(e){
        e.stopPropagation();
        if(e.which==13){
            selectPeriodRadioButton($(this));
        }
    })
});

//Keyboard controls for Top Controls Calendar through tab and enter.
window.addEventListener('DOMContentLoaded', function () {
    $('.periodSelector').keydown(function(e){
        toggleCalendar(e);
    })

    blockPropegation();

    $('.periodSelector .form-radio').keydown(function(e){
        e.stopPropagation();
        if(e.which==13){
            selectPeriodRadioButton($(this));
        }
    })
});

function toggleCalendar(e){
    var calendarOpen = $('.periodSelector').hasClass('expanded');

    if(e.which==13){
        if(calendarOpen){
            $('.periodSelector').removeClass('expanded');
        }else{
            $('.periodSelector').addClass('expanded');
        }
    }
}

function selectPeriodRadioButton(button){
    $('.periodSelector .form-radio').removeClass('checked');
    button.addClass('checked');
    button.find('input').click();

    blockPropegation();
}

function blockPropegation(){
    $('.ui-datepicker-month, .ui-datepicker-year, .periodSelector td a').keydown(function(e){
        e.stopPropagation();
    })
}

//refresh page short cut 'r'
$(function () {
  piwikHelper.registerShortcut('r', _pk_translate('CoreHome_ShortcutRefresh'), function (event) {
    if (event.altKey) {
      return;
    }
    if (event.preventDefault) {
      event.preventDefault();
    } else {
      event.returnValue = false; // IE
    }

    var Matomo = window.CoreHome.Matomo;
    var hashParsed = window.CoreHome.MatomoUrl.hashParsed.value;

    Matomo.postEvent('loadPage', hashParsed.category, hashParsed.subcategory);
  });
});