

//Date Picker
$(".ndate-hour").persianDatepicker({
    observer: false,
    initialValue: false,
    format: 'YYYY/MM/DD',
    altField: '.ndate-hour-field'
});

// //Date Picker
// $(".ndate-day-from").persianDatepicker({
//     observer: false,
//     initialValue: false,
//     format: 'YYYY/MM/DD',
//     altField: '.ndate-day-from-field'
// });




// buffers, Fake Data For Problems
var _hourly_data = [0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 30];
var _daily_data = [0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 0, 10, 5, 2, 20, 28, 25, 10, 20, 12, 9, 12, 30];
//var _hourly_lable = ['-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-'];
var _daily_lable = ['-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-'];


var url_params = get_all_url_params(window.location.href); // get the target id from url link
var _current_time = Math.round(new Date().getTime() / 1000); // current time in seconds

// Make Jalaali Time With Help From Milaadi Time
var dateObj = new Date();
var month = dateObj.getUTCMonth() + 1; //months from 1-12
var day = dateObj.getUTCDate();
var year = dateObj.getUTCFullYear();

// Convert Milaadi to Jalaili
var date = toJalaali(year, month, day);

var valid_mounths = [];
var _month = parseInt(date.jm);
//Create array of options to be added
for (var i = 1; i <= 6; ++i, --_month) {

    if (_month < 1)
        _month = 12;

    valid_mounths.push(_month);
}


var myDiv = document.getElementById("_time");

//Create and append select list
var selectList = document.createElement("select");
selectList.id = "month-select";
myDiv.appendChild(selectList);

//Create and append the options
for (var i = 0; i < valid_mounths.length; i++) {
    var option = document.createElement("option");
    option.value = valid_mounths[i];

    switch (valid_mounths[i])
    {
        case 1:
            option.text = 'فروردین';
            break;
        case 2:
            option.text = 'اردیبهشت';
            break;
        case 3:
            option.text = 'خرداد';
            break;
        case 4:
            option.text = 'تیر';
            break;
        case 5:
            option.text = 'مرداد';
            break;
        case 6:
            option.text = 'شهریور';
            break;
        case 7:
            option.text = 'مهر';
            break;
        case 8:
            option.text = 'آبان';
            break;
        case 9:
            option.text = 'آذر';
            break;
        case 10:
            option.text = 'دی';
            break;
        case 11:
            option.text = 'بهمن';
            break;
        case 12:
            option.text = 'اسفند';
            break;
        default:
            option.text = 'error';
    }
    selectList.appendChild(option);
}


// *shh* get the hourly informations from db
$.ajax({ //first hourly informaition chart
    // url: "/site/sed1",
    url: "/exhibition/hourly-visit-to-array",
    async: false,
    type: 'POST',
    dataType: 'json',
    data: {
        date: _current_time, // in second for
        id: url_params.pid, // product id
    },
    success: function (data) {
        if (data.status) {
            _hourly_data = data.data;
        } else {
            console.log('problem in ajax hourly');
            alert('خطای اتصال به سرور در نمودار ساعتی ! با ادمین و پشتیبانی تماس بگیرید.');
        }

    }
});

// *shh* get the daily informations from db
$.ajax({ //first daily informaition chart
    // url: "/site/sed2",
    url: "/exhibition/daily-visit-to-array",
    async: false,
    type: 'POST',
    dataType: 'json',
    data: {
        id: url_params.pid,
        month: date.jm,
    }, //2937600 is 24*60*68*30

    success: function (data) {
        if (data.status) {
            temp = make_lable_and_data_for_chart_from_query_result(data.data);
            _daily_lable = temp[0];
            _daily_data = temp[1];
        } else {
            console.log('problem in ajax daily');
            alert('خطای اتصال به سرور در نمودار روزانه ! با ادمین و پشتیبانی تماس بگیرید.');
        }
    }
});


//*shh* run at first load  for draw chart js || Hourly View
var ctx = document.getElementById('hourly-view').getContext('2d');
var hourly_chart = new Chart(ctx, {
    // The type of chart we want to create
    type: 'line',

    // The data for our dataset
    data: {
        labels: ["1AM", "2AM", "3AM", "4AM", "5AM","6AM","7AM","8AM", "9AM", "10AM", "11AM", "12AM", "1PM", "2PM", "3PM", "4PM", "5PM", "6PM", "7PM", "8PM", "9PM", "10PM", "11PM", "12PM"],
        datasets: [{
            label: "آمار بازدید ساعتی",
            backgroundColor: 'rgba(0,0,0,.02)',
            borderColor: 'rgb(230,200,109)',
            data: _hourly_data,
            pointRadius: 8,
            pointHoverRadius: 16,
            pointHoverBackgroundColor: 'rgba(255,255,255,1)',
            pointBackgroundColor: 'rgba(0,0,0,1)',
            pointBorderColor: 'rgba(255,255,255,1)',
            pointHoverBorderColor: 'rgba(0,0,0,1)',
            borderWidth: 2
        }]
    },

    // Configuration options go here
    options: {
        legend: {
            display: false,
        }
    }
});


//*shh* run at first load  for draw chart js || Daily View
var ctx = document.getElementById('daily-view').getContext('2d');
var daily_chart = new Chart(ctx, {
    // The type of chart we want to create
    type: 'line',

    // The data for our dataset
    data: {
        labels: _daily_lable,
        datasets: [{
            label: "آمار بازدید روزانه",
            backgroundColor: 'rgba(0,0,0,.02)',
            borderColor: 'rgb(230,200,109)',
            data: _daily_data,
            pointRadius: 8,
            pointHoverRadius: 16,
            pointHoverBackgroundColor: 'rgba(255,255,255,1)',
            pointBackgroundColor: 'rgba(0,0,0,1)',
            pointBorderColor: 'rgba(255,255,255,1)',
            pointHoverBorderColor: 'rgba(0,0,0,1)',
            borderWidth: 2,
        }]
    },

    // Configuration options go here
    options: {
        legend: {
            display: false,
        }
    }
});




$('.hourly-statistic').on('click touchstart', function (event) {
    event.preventDefault();

    var pure_date_in_second = $('.ndate-hour-field').val() / 1000;

    $.ajax({
        url: "/exhibition/hourly-visit-to-array",
        // url: "/site/sed1",
        type: 'POST',
        dataType: 'json',
        data: {
            date: pure_date_in_second,
            id: url_params.pid
        },

        success: function (data) {
            if (data.status) {
                update_hourly_chart(data);
            } else {
                alert('خطای اتصال به سرور ! با ادمین و پشتیبانی تماس بگیرید.');
            }

        }
    });
});


// *shh* reqeust to update chart for monthly statistic
$('#month-select').on('change touchstart', function (event) {
    event.preventDefault();
    _month_value = this.value;


    $.ajax({
        url: "/exhibition/daily-visit-to-array",
        // url: "/site/sed2",
        async: false,
        type: 'POST',
        dataType: 'json',
        data: {
            id: url_params.pid,
            month: _month_value,
            // day: date.jd
        },

        success: function (data) {
            if (data.status) {
                console.log(data);
                update_daily_chart(data);
            } else {
                console.log('problem in ajax daily');
                alert('خطای اتصال به سرور در نمودار روزانه ! با ادمین و پشتیبانی تماس بگیرید.');
            }
        }
    });
});

//
// $('.statistics-arrow-post').on('click touchstart', function (event) {
//     event.preventDefault();
//
//     $.ajax({
//         url: "/site/sed2",
//         async: false,
//         type: 'POST',
//         dataType: 'json',
//         data: {
//             id: url_params.pid,
//             month: date.jm,
//             day: date.jd
//         }, //2937600 is 24*60*68*30
//
//         success: function (data) {
//             if (data.status) {
//
//                 temp = make_lable_and_data_for_chart_from_query_result(data.data);
//                 _daily_lable = temp[0];
//                 _daily_data = temp[1];
//             } else {
//                 console.log('problem in ajax daily');
//                 alert('خطای اتصال به سرور در نمودار روزانه ! با ادمین و پشتیبانی تماس بگیرید.');
//             }
//         }
//     });
//
// });



// *shh*
// ============================================   custom fuctions ==========================================================


// *shh* function name is readable enough bro! :)
function get_all_url_params(url) {

    // get query string from url (optional) or window
    var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

    // we'll store the parameters here
    var obj = {};

    // if query string exists
    if (queryString) {

        // stuff after # is not part of query string, so get rid of it
        queryString = queryString.split('#')[0];

        // split our query string into its component parts
        var arr = queryString.split('&');

        for (var i = 0; i < arr.length; i++) {
            // separate the keys and the values
            var a = arr[i].split('=');

            // in case params look like: list[]=thing1&list[]=thing2
            var paramNum = undefined;
            var paramName = a[0].replace(/\[\d*\]/, function (v) {
                paramNum = v.slice(1, -1);
                return '';
            });

            // set parameter value (use 'true' if empty)
            var paramValue = typeof(a[1]) === 'undefined' ? true : a[1];

            // (optional) keep case consistent
            paramName = paramName.toLowerCase();
            paramValue = paramValue.toLowerCase();

            // if parameter name already exists
            if (obj[paramName]) {
                // convert value to array (if still string)
                if (typeof obj[paramName] === 'string') {
                    obj[paramName] = [obj[paramName]];
                }
                // if no array index number specified...
                if (typeof paramNum === 'undefined') {
                    // put the value on the end of the array
                    obj[paramName].push(paramValue);
                }
                // if array index number specified...
                else {
                    // put the value at that index number
                    obj[paramName][paramNum] = paramValue;
                }
            }
            // if param name doesn't exist yet, set it
            else {
                obj[paramName] = paramValue;
            }
        }
    }

    return obj;
}

// *shh* my codes(names) explains my comments ! :)
function persian_to_englsih_digit(number) {
    return number.replace(/([٠١٢٣٤٥٦٧٨٩])|([۰۱۲۳۴۵۶۷۸۹])/g, function (m, $1, $2) {
        return m.charCodeAt(0) - ($1 ? 1632 : 1776);
    });
}

// *shh* runs in ajax query after each click on 'Moshaahede Amaar' of Hourly chart
function update_hourly_chart(data) {
//Hourly View
    hourly_chart.data.datasets[0].data = data['data'];
    hourly_chart.data.labels = ["1AM", "2AM", "3AM", "4AM", "5AM","6AM","7AM","8AM", "9AM", "10AM", "11AM", "12AM", "1PM", "2PM", "3PM", "4PM", "5PM", "6PM", "7PM", "8PM", "9PM", "10PM", "11PM", "12PM"];
    console.log(hourly_chart.data);
    hourly_chart.update();
}

// *shh* runs in ajax query after each click on 'Moshaahede Amaar' of Daily chart
function update_daily_chart(data) {

    temp = make_lable_and_data_for_chart_from_query_result(data.data);
    _daily_lable = temp[0];
    _daily_data = temp[1];

    daily_chart.data.datasets[0].data = _daily_data;
    daily_chart.data.labels = _daily_lable;
    daily_chart.update();
}

// *shh* get the arrays with ['month/day' : visit_count] patternn and make lables and datas for chart.js
function make_lable_and_data_for_chart_from_query_result(data) {

    var _labels = [];
    var _data = [];

    $.each(data, function (index, value) {
        _labels.push(index);
        _data.push(value);
    });


    return [_labels, _data];
}



// *shh*
// ======================        Convert Date From Milaadi To Jalali         =======================
// Resource : https://github.com/jalaali/jalaali-js
/*
 Converts a Gregorian date to Jalaali.
 */
function toJalaali(gy, gm, gd) {
    if (Object.prototype.toString.call(gy) === '[object Date]') {
        gd = gy.getDate()
        gm = gy.getMonth() + 1
        gy = gy.getFullYear()
    }
    return d2j(g2d(gy, gm, gd))
}

/*
 Converts a Jalaali date to Gregorian.
 */
function toGregorian(jy, jm, jd) {
    return d2g(j2d(jy, jm, jd))
}

/*
 Checks whether a Jalaali date is valid or not.
 */
function isValidJalaaliDate(jy, jm, jd) {
    return jy >= -61 && jy <= 3177 &&
        jm >= 1 && jm <= 12 &&
        jd >= 1 && jd <= jalaaliMonthLength(jy, jm)
}

/*
 Is this a leap year or not?
 */
function isLeapJalaaliYear(jy) {
    return jalCal(jy).leap === 0
}

/*
 Number of days in a given month in a Jalaali year.
 */
function jalaaliMonthLength(jy, jm) {
    if (jm <= 6) return 31
    if (jm <= 11) return 30
    if (isLeapJalaaliYear(jy)) return 30
    return 29
}

/*
 This function determines if the Jalaali (Persian) year is
 leap (366-day long) or is the common year (365 days), and
 finds the day in March (Gregorian calendar) of the first
 day of the Jalaali year (jy).
 @param jy Jalaali calendar year (-61 to 3177)
 @return
 leap: number of years since the last leap year (0 to 4)
 gy: Gregorian year of the beginning of Jalaali year
 march: the March day of Farvardin the 1st (1st day of jy)
 @see: http://www.astro.uni.torun.pl/~kb/Papers/EMP/PersianC-EMP.htm
 @see: http://www.fourmilab.ch/documents/calendar/
 */
function jalCal(jy) {
    // Jalaali years starting the 33-year rule.
    var breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210
        , 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178
    ]
        , bl = breaks.length
        , gy = jy + 621
        , leapJ = -14
        , jp = breaks[0]
        , jm
        , jump
        , leap
        , leapG
        , march
        , n
        , i

    if (jy < jp || jy >= breaks[bl - 1])
        throw new Error('Invalid Jalaali year ' + jy)

    // Find the limiting years for the Jalaali year jy.
    for (i = 1; i < bl; i += 1) {
        jm = breaks[i]
        jump = jm - jp
        if (jy < jm)
            break
        leapJ = leapJ + div(jump, 33) * 8 + div(mod(jump, 33), 4)
        jp = jm
    }
    n = jy - jp

    // Find the number of leap years from AD 621 to the beginning
    // of the current Jalaali year in the Persian calendar.
    leapJ = leapJ + div(n, 33) * 8 + div(mod(n, 33) + 3, 4)
    if (mod(jump, 33) === 4 && jump - n === 4)
        leapJ += 1

    // And the same in the Gregorian calendar (until the year gy).
    leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150

    // Determine the Gregorian date of Farvardin the 1st.
    march = 20 + leapJ - leapG

    // Find how many years have passed since the last leap year.
    if (jump - n < 6)
        n = n - jump + div(jump + 4, 33) * 33
    leap = mod(mod(n + 1, 33) - 1, 4)
    if (leap === -1) {
        leap = 4
    }

    return {
        leap: leap
        , gy: gy
        , march: march
    }
}

/*
 Converts a date of the Jalaali calendar to the Julian Day number.
 @param jy Jalaali year (1 to 3100)
 @param jm Jalaali month (1 to 12)
 @param jd Jalaali day (1 to 29/31)
 @return Julian Day number
 */
function j2d(jy, jm, jd) {
    var r = jalCal(jy)
    return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1
}

/*
 Converts the Julian Day number to a date in the Jalaali calendar.
 @param jdn Julian Day number
 @return
 jy: Jalaali year (1 to 3100)
 jm: Jalaali month (1 to 12)
 jd: Jalaali day (1 to 29/31)
 */
function d2j(jdn) {
    var gy = d2g(jdn).gy // Calculate Gregorian year (gy).
        , jy = gy - 621
        , r = jalCal(jy)
        , jdn1f = g2d(gy, 3, r.march)
        , jd
        , jm
        , k

    // Find number of days that passed since 1 Farvardin.
    k = jdn - jdn1f
    if (k >= 0) {
        if (k <= 185) {
            // The first 6 months.
            jm = 1 + div(k, 31)
            jd = mod(k, 31) + 1
            return {
                jy: jy
                , jm: jm
                , jd: jd
            }
        } else {
            // The remaining months.
            k -= 186
        }
    } else {
        // Previous Jalaali year.
        jy -= 1
        k += 179
        if (r.leap === 1)
            k += 1
    }
    jm = 7 + div(k, 30)
    jd = mod(k, 30) + 1
    return {
        jy: jy
        , jm: jm
        , jd: jd
    }
}

/*
 Calculates the Julian Day number from Gregorian or Julian
 calendar dates. This integer number corresponds to the noon of
 the date (i.e. 12 hours of Universal Time).
 The procedure was tested to be good since 1 March, -100100 (of both
 calendars) up to a few million years into the future.
 @param gy Calendar year (years BC numbered 0, -1, -2, ...)
 @param gm Calendar month (1 to 12)
 @param gd Calendar day of the month (1 to 28/29/30/31)
 @return Julian Day number
 */
function g2d(gy, gm, gd) {
    var d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4)
        + div(153 * mod(gm + 9, 12) + 2, 5)
        + gd - 34840408
    d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752
    return d
}

/*
 Calculates Gregorian and Julian calendar dates from the Julian Day number
 (jdn) for the period since jdn=-34839655 (i.e. the year -100100 of both
 calendars) to some millions years ahead of the present.
 @param jdn Julian Day number
 @return
 gy: Calendar year (years BC numbered 0, -1, -2, ...)
 gm: Calendar month (1 to 12)
 gd: Calendar day of the month M (1 to 28/29/30/31)
 */
function d2g(jdn) {
    var j
        , i
        , gd
        , gm
        , gy
    j = 4 * jdn + 139361631
    j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908
    i = div(mod(j, 1461), 4) * 5 + 308
    gd = div(mod(i, 153), 5) + 1
    gm = mod(div(i, 153), 12) + 1
    gy = div(j, 1461) - 100100 + div(8 - gm, 6)
    return {
        gy: gy
        , gm: gm
        , gd: gd
    }
}

/*
 Utility helper functions.
 */

function div(a, b) {
    return ~~(a / b)
}

function mod(a, b) {
    return a - ~~(a / b) * b
}