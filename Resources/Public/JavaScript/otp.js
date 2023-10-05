const otp_inputs = document.querySelectorAll('.otp__digit')
const mykey = '0123456789'.split('')

otp_inputs.forEach((_) => {
  _.addEventListener('keyup', handle_next_input)
  _.addEventListener('paste', handle_paste)
})

function handle_next_input(event) {
  const current = event.target
  let index = parseInt(current.classList[1].split('__')[2])
  current.value = event.key

  if (event.keyCode === 8 && index > 1) {
    current.previousElementSibling.focus()
  }
  if (index < 6 && mykey.indexOf(''+event.key+'') !== -1) {
    current.nextElementSibling.focus()
  }
  send_otp(current.form)
}

function handle_paste(event) {
  event.preventDefault()
  const current = event.target
  let content = (event.clipboardData || window.clipboardData).getData('text').trim()
  if (content.match(/[0-9]{6}/)) {
    setTimeout(() => {
      for (let i = 0; i < 6; i++) {
        otp_inputs[i].value = content[i]
      }
      send_otp(current.form)
    }, 200)
  }
}

function send_otp(form) {
  var _finalKey = ''
  for (let {value} of otp_inputs) {
    _finalKey += value
  }
  if (_finalKey.length === 6) {
    document.getElementById('mfaprotect-otp').value = _finalKey
    form.submit()
  }
}
