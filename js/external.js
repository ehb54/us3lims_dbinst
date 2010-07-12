// JavaScript routine to activate link in new window

function initLinks() {
  for (i in document.links) {
    link = document.links[i];
    if (link.rel && link.rel.indexOf('external')!=-1) {
      link.onclick = onExternalLinkActivate;
      link.onkeypress = onExternalLinkActivate;
    }
  }
}

function onExternalLinkActivate() {
  window.open(this.href);
  return false;
}

window.onload = initLinks;

