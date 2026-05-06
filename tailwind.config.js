/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './index.php',
    './src/**/*.{html,js,php}',
  ],
  theme: {
    extend: {
      fontFamily: {
        nunito: ['Nunito', 'sans-serif'],
        mono:   ['JetBrains Mono', 'monospace'],
      },
      colors: {
        bg:       '#f0f7ee',
        bg2:      '#ffffff',
        bg3:      '#e8f4e5',
        surface:  '#ffffff',
        border:   '#d4e8cf',
        border2:  '#b8d9b1',

        green:    '#2d9e5f',
        green2:   '#3dbf74',
        green3:   '#7ddba0',
        greenlt:  '#e6f7ed',

        blue:     '#1a8fc4',
        blue2:    '#35aadf',
        bluelt:   '#e3f4fc',

        orange:   '#e07820',
        orange2:  '#f59d45',
        orangelt: '#fff0e0',

        yellow:   '#d4a017',
        yellowlt: '#fef9e7',

        red:      '#e03d3d',
        redlt:    '#fdeaea',

        purple:   '#7c4dbc',
        purplelt: '#f2ecfb',

        text1:    '#1a2e1a',
        text2:    '#3d5c3a',
        text3:    '#6b8f68',
        text4:    '#9ab899',
      },
      borderRadius: {
        card:  '18px',
        card2: '12px',
      },
      boxShadow: {
        card:  '0 2px 12px rgba(45,158,95,0.10)',
        card2: '0 8px 32px rgba(45,158,95,0.14)',
      },
      fontSize: {
        '2xs': '0.58rem',
        '3xs': '0.52rem',
        '4xs': '0.48rem',
        '5xs': '0.44rem',
      },
      height: {
        'gauge': '88px',
        'tank':  '110px',
      },
      width: {
        'tank': '54px',
      },
      keyframes: {
        livepulse: {
          '0%, 100%': { transform: 'scale(1)', opacity: '1', boxShadow: '0 0 0 0 rgba(61,191,116,0.5)' },
          '50%':      { transform: 'scale(1.3)', opacity: '0.8', boxShadow: '0 0 0 5px rgba(61,191,116,0)' },
        },
        pumporb: {
          '0%, 100%': { boxShadow: '0 0 0 6px rgba(61,191,116,0.15), 0 0 30px rgba(61,191,116,0.25)' },
          '50%':      { boxShadow: '0 0 0 12px rgba(61,191,116,0.1), 0 0 50px rgba(61,191,116,0.4)' },
        },
        tankwave: {
          '0%, 100%': { transform: 'translateX(0) scaleX(1)' },
          '50%':      { transform: 'translateX(3px) scaleX(1.1)' },
        },
      },
      animation: {
        livepulse: 'livepulse 2s ease-in-out infinite',
        pumporb:   'pumporb 2.8s ease-in-out infinite',
        tankwave:  'tankwave 3s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
