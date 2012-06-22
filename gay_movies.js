function handleDragDropEvent(oEvent) {
  var oTextbox = document.getElementById("txt1");
  oTextbox.value +=  oEvent.type + "\n";
}

var about_stile = "top=10, left=10, width=400, height=260 status=no, menubar=no, toolbar=no, scrollbar=no";

function AboutPopup(apri) {
  onclick=window.open(apri, "", about_stile);
}

var faq_stile = "top=10, left=10, width=600, height=400 status=no, menubar=no, toolbar=no, scrollbars=1";

function FAQPopup(apri) {
  onclick=window.open(apri, "", faq_stile);
}

var faq_stile = "top=10, left=10, width=600, height=400 status=no, menubar=no, toolbar=no, scrollbars=1";

function TOSPopup(apri) {
  onclick=window.open(apri, "", faq_stile);
}

