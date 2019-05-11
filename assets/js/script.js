function confirmAction() {
  if (!confirm('You sure?')) {
    alert('Nothing happened');
    return false;
  }
}

function confirmPassword() {
  return document.getElementById('password').value == document.getElementById('password-confirmation').value ? true : alert('Passwords are not same');
}
