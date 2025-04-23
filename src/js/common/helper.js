export function formatDate(date, format) {
  // PHP-ähnliche Formatierungscodes
  const formatCodes = {
    d: () => date.getDate().toString().padStart(2, '0'),
    j: () => date.getDate(),
    D: () => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][date.getDay()],
    l: () =>
      ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][date.getDay()],
    F: () =>
      [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
      ][date.getMonth()],
    m: () => (date.getMonth() + 1).toString().padStart(2, '0'),
    n: () => date.getMonth() + 1,
    M: () =>
      ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][
        date.getMonth()
      ],
    Y: () => date.getFullYear(),
    y: () => date.getFullYear().toString().slice(-2),
  };

  let result = '';
  for (let i = 0; i < format.length; i++) {
    const char = format.charAt(i);
    if (formatCodes[char]) {
      result += formatCodes[char]();
    } else {
      result += char;
    }
  }
  return result;
}
