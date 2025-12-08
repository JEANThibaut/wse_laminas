// Faction HUD: decorations, logos, progress bars

var logoImages = {
  cdc: '/assets/img/CDC_logo.png',
  cgs: '/assets/img/CGS_logo.png'
};

function createElement(tagName, className) {
  var element = document.createElement(tagName);
  if (className) {
    element.className = className;
  }
  return element;
}

function createCardFrame() {
  return createElement('div', 'card-frame');
}

function createCardCorner(position) {
  return createElement('div', 'card-corner ' + position);
}

function createCardTick(side) {
  return createElement('div', 'card-tick ' + side);
}

function createCardDots() {
  return createElement('div', 'card-dots');
}

function decorateSingleCard(card) {
  if (!card || card.dataset.factionDecorated === '1') {
    return;
  }

  var frame = createCardFrame();
  card.insertBefore(frame, card.firstChild);

  var cornerPositions = ['tl', 'tr', 'bl', 'br'];
  for (var i = 0; i < cornerPositions.length; i++) {
    var corner = createCardCorner(cornerPositions[i]);
    card.appendChild(corner);
  }

  var leftTick = createCardTick('left');
  var rightTick = createCardTick('right');
  card.appendChild(leftTick);
  card.appendChild(rightTick);

  card.dataset.factionDecorated = '1';
}

function decorateCardBody(card) {
  if (!card) {
    return;
  }

  var body = card.querySelector('.card-body');
  if (!body || body.dataset.dotsDecorated === '1') {
    return;
  }

  var dots = createCardDots();
  body.insertBefore(dots, body.firstChild);
  body.dataset.dotsDecorated = '1';
}

function decorateCard(card) {
  decorateSingleCard(card);
  decorateCardBody(card);
}

function decorateAllCards() {
  var cards = document.querySelectorAll('.hud-card-large');
  for (var i = 0; i < cards.length; i++) {
    decorateCard(cards[i]);
  }
}

function applyLogoToCard(card, logoId) {
  if (!card) {
    return;
  }

  var frontFace = card.querySelector('.title-mask.front') || card.querySelector('.title-mask');
  var logoKey = logoId || (frontFace && frontFace.dataset && frontFace.dataset.logo) || 'cdc';
  var logoUrl = logoImages[logoKey] || logoImages.cdc;

  var supportsMask = false;
  try {
    supportsMask = ('maskImage' in document.body.style) || ('webkitMaskImage' in document.body.style);
  } catch (error) {
    supportsMask = false;
  }

  var faces = card.querySelectorAll('.title-mask');
  for (var i = 0; i < faces.length; i++) {
    var face = faces[i];
    if (supportsMask) {
      face.style.backgroundImage = 'none';
      face.style.webkitMaskImage = 'url(' + logoUrl + ')';
      face.style.webkitMaskRepeat = 'no-repeat';
      face.style.webkitMaskPosition = 'center';
      face.style.webkitMaskSize = 'contain';
      face.style.maskImage = 'url(' + logoUrl + ')';
      face.style.maskRepeat = 'no-repeat';
      face.style.maskPosition = 'center';
      face.style.maskSize = 'contain';
    } else {
      face.style.backgroundImage = 'url(' + logoUrl + ')';
      face.style.backgroundRepeat = 'no-repeat';
      face.style.backgroundPosition = 'center';
      face.style.backgroundSize = 'contain';
    }
  }
}

function applyLogosToAllCards() {
  var cards = document.querySelectorAll('.title-card');
  for (var i = 0; i < cards.length; i++) {
    var card = cards[i];
    var frontFace = card.querySelector('.title-mask.front') || card.querySelector('.title-mask');
    var logoId = (frontFace && frontFace.dataset && frontFace.dataset.logo) || 'cdc';
    applyLogoToCard(card, logoId);

    if (card.dataset && card.dataset.rotate === '1') {
      startCardRotation(card);
    }
  }
}

function startCardRotation(card) {
  if (!card) {
    return;
  }
  card.classList.add('is-rotating');
}

function initializeProgressBars() {
  var bars = document.querySelectorAll('.progress-bar');

  for (var i = 0; i < bars.length; i++) {
    var bar = bars[i];
    var fill = bar.querySelector('.progress-fill');
    if (!fill) {
      continue;
    }

    var unit = bar.dataset.unit || fill.dataset.unit || 'unit';
    var avance = Number(bar.dataset.avance || fill.dataset.avance || 0);
    var echelle = Number(bar.dataset.echelle || fill.dataset.echelle || 0);

    var targetPercent = 0;
    var displayText = '';

    if (String(unit).toLowerCase() === 'pourcent') {
      targetPercent = Math.max(0, Math.min(100, avance || 0));
      displayText = Math.round(targetPercent) + '%';
    } else {
      if (echelle > 0) {
        targetPercent = (avance / echelle) * 100;
        targetPercent = Math.max(0, Math.min(100, targetPercent));
      }
      displayText = avance + ' / ' + echelle;
    }

    bar.setAttribute('role', 'progressbar');
    bar.setAttribute('aria-valuemin', '0');
    bar.setAttribute('aria-valuemax', '100');
    bar.setAttribute('aria-valuenow', String(Math.round(targetPercent)));

    var percentLabel = bar.querySelector('.progress-label');
    if (!percentLabel) {
      percentLabel = document.createElement('div');
      percentLabel.className = 'progress-label';
      bar.appendChild(percentLabel);
    }
    percentLabel.textContent = displayText;

    var innerLabel = fill.querySelector('.progress-inner-label');
    if (!innerLabel) {
      var labelText = bar.dataset.label || fill.dataset.label || '';
      if (labelText) {
        innerLabel = document.createElement('div');
        innerLabel.className = 'progress-inner-label';
        innerLabel.textContent = labelText;
        fill.appendChild(innerLabel);
      }
    }

    fill.style.width = '0%';
    var roundedPercent = Math.round(targetPercent);
    requestAnimationFrame(function(fillElement, percent) {
      return function() {
        requestAnimationFrame(function() {
          fillElement.style.width = percent + '%';
        });
      };
    }(fill, roundedPercent));
  }
}

function handleProgressBarClick(event) {
  var bar = event.currentTarget;
  var label = bar.dataset.label || 'Objectif';
  console.log('Progress bar clicked:', label);
  openPopup(label);
}

function addProgressBarClickListeners() {
  var bars = document.querySelectorAll('.progress-bar');
  for (var i = 0; i < bars.length; i++) {
    bars[i].style.cursor = 'pointer';
    bars[i].addEventListener('click', handleProgressBarClick);
  }
}

function createPopupOverlay() {
  var overlay = createElement('div', 'popup-overlay');
  overlay.addEventListener('click', function(event) {
    if (event.target === overlay) {
      closePopup();
    }
  });
  return overlay;
}

function createPopupContainer() {
  return createElement('div', 'popup-container');
}

function createPopupHeader(title) {
  var header = createElement('div', 'popup-header');
  
  var titleElement = createElement('div', 'popup-title');
  titleElement.textContent = title;
  
  var closeButton = createElement('button', 'popup-close');
  closeButton.textContent = '×';
  closeButton.setAttribute('aria-label', 'Fermer');
  closeButton.addEventListener('click', closePopup);
  
  header.appendChild(titleElement);
  header.appendChild(closeButton);
  
  return header;
}

function createPopupContent() {
  var content = createElement('div', 'popup-content');
  
  var cardsContainer = createElement('div', 'hud-cards');
  
  var card1 = createElement('div', 'hud-card-large panel-corners');
  var header1 = createElement('div', 'card-header');
  var headerDiv1 = createElement('div', '');
  var cardTitle1 = createElement('div', 'card-title');
  cardTitle1.textContent = 'Détails';
  var cardSub1 = createElement('div', 'card-sub');
  cardSub1.textContent = 'Information';
  headerDiv1.appendChild(cardTitle1);
  headerDiv1.appendChild(cardSub1);
  var chip1 = createElement('div', 'hud-chip');
  header1.appendChild(headerDiv1);
  header1.appendChild(chip1);
  
  var body1 = createElement('div', 'card-body');
  var text1 = createElement('p', 'card-text');
  text1.textContent = 'Contenu de la popup avec les mêmes cards HUD.';
  body1.appendChild(text1);
  
  var footer1 = createElement('div', 'card-footer');
  var btn1 = createElement('button', 'faction-btn');
  btn1.textContent = 'Action';
  footer1.appendChild(btn1);
  
  card1.appendChild(header1);
  card1.appendChild(body1);
  card1.appendChild(footer1);
  
  cardsContainer.appendChild(card1);
  content.appendChild(cardsContainer);
  
  return content;
}

function openPopup(title) {
  var existingOverlay = document.querySelector('.popup-overlay');
  if (existingOverlay) {
    closePopup();
  }
  
  var overlay = createPopupOverlay();
  var container = createPopupContainer();
  var header = createPopupHeader(title);
  var content = createPopupContent();
  
  container.appendChild(header);
  container.appendChild(content);
  overlay.appendChild(container);
  document.body.appendChild(overlay);
  
  requestAnimationFrame(function() {
    overlay.classList.add('is-visible');
    decorateCard(container.querySelector('.hud-card-large'));
  });
}

function closePopup() {
  var overlay = document.querySelector('.popup-overlay');
  if (!overlay) {
    return;
  }
  
  overlay.classList.remove('is-visible');
  setTimeout(function() {
    if (overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }
  }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
  decorateAllCards();
  applyLogosToAllCards();
  initializeProgressBars();
  addProgressBarClickListeners();
});
