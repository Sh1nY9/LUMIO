// 디자인 레퍼런스 카드 렌더 · 필터 · 검색
// 핀터레스트형 UI: 이미지 중심의 masonry 그리드, 설명은 호버 시에만 드러낸다.

const references = [
  { image: 'https://i.pinimg.com/1200x/c4/35/05/c43505d54f9753b204b33d3c276ae3ec.jpg', category: 'web',      categoryLabel: 'Web design', title: 'Quiet interface',    description: '여백과 낮은 대비로 완성한 차분한 인터페이스', color: 'violet' },
  { image: 'https://i.pinimg.com/1200x/e7/40/f7/e740f76f367cd49ba18e72f4d17404e0.jpg', category: 'branding', categoryLabel: 'Branding',    title: 'Soft signal',       description: '부드러운 컬러와 타이포그래피의 브랜드 시스템', color: 'blue' },
  { image: 'https://i.pinimg.com/1200x/15/bc/21/15bc2141e9b6e68d6d16541258381b08.jpg', category: 'graphic',  categoryLabel: 'Graphic',     title: 'Electric type',     description: '빛과 입자가 만드는 실험적인 그래픽 포스터', color: 'cyan' },
  { image: 'https://i.pinimg.com/1200x/83/89/7c/83897cec70be607b6a72796a62adb2f1.jpg', category: 'web',      categoryLabel: 'Web design', title: 'Editorial flow',    description: '매거진처럼 읽히는 콘텐츠 중심의 화면 구성', color: 'peach' },
  { image: 'https://i.pinimg.com/736x/96/5a/21/965a2186100e1e1324fee780f0a2de88.jpg',  category: 'branding', categoryLabel: 'Branding',    title: 'Object & form',     description: '제품과 그래픽을 하나의 언어로 묶은 방향성', color: 'lime' },
  { image: 'https://i.pinimg.com/736x/33/df/0b/33df0b32931051dbf173eeadff35de5e.jpg',  category: 'graphic',  categoryLabel: 'Graphic',     title: 'After image',       description: '잔상과 노이즈를 활용한 디지털 아트워크', color: 'indigo' },
  { image: 'https://i.pinimg.com/1200x/56/a7/6f/56a76f57b9e5d3a92eef8d815884252d.jpg', category: 'web',      categoryLabel: 'Web design', title: 'Night mode',        description: '어두운 화면 안에서 정보의 위계를 만드는 방법', color: 'night' },
  { image: 'https://i.pinimg.com/736x/37/91/a7/3791a7ef1d2624dde6a97c2bd04f0893.jpg',  category: 'graphic',  categoryLabel: 'Graphic',     title: 'New perspective',   description: '익숙한 형태를 낯설게 바라보는 시각 실험', color: 'rose' },
  { image: 'https://i.pinimg.com/1200x/37/da/ac/37daacff9f62ed7715ae5f1bbcef5c42.jpg', category: 'branding', categoryLabel: 'Branding',    title: 'Material language', description: '질감과 오브젝트로 확장한 브랜드 무드', color: 'peach' },
  { image: 'https://i.pinimg.com/1200x/4a/62/e2/4a62e26f9dac80b39f179474aa71308f.jpg', category: 'web',      categoryLabel: 'Web design', title: 'Open structure',    description: '정보를 가볍게 펼쳐 보여주는 화면 구조', color: 'blue' },
  { image: 'https://i.pinimg.com/736x/c4/4d/47/c44d47e2eeda5c9b56dc8883b819f2b8.jpg',  category: 'graphic',  categoryLabel: 'Graphic',     title: 'Color frequency',   description: '색의 간격과 밀도로 만든 시각적 리듬', color: 'cyan' },
  { image: 'https://i.pinimg.com/736x/eb/21/64/eb216475dc0641794a09355304875e4e.jpg',  category: 'branding', categoryLabel: 'Branding',    title: 'Still object',      description: '정적인 이미지 안에 긴장감을 남기는 구성', color: 'lime' },
  { image: 'https://i.pinimg.com/736x/6b/62/61/6b6261ed5bde03679fb6650fc8212956.jpg',  category: 'web',      categoryLabel: 'Web design', title: 'Signal / noise',    description: '노이즈와 선명함 사이의 디지털 인터페이스', color: 'night' },
  { image: 'https://i.pinimg.com/1200x/79/ec/db/79ecdb90400dec646bcfd7011357d551.jpg', category: 'graphic',  categoryLabel: 'Graphic',     title: 'Blue interval',     description: '차가운 색면과 여백으로 만든 시각적 호흡', color: 'blue' },
  { image: 'https://i.pinimg.com/736x/bd/fc/42/bdfc42305cbd00a37eadf3324c2914f7.jpg',  category: 'branding', categoryLabel: 'Branding',    title: 'Form study',        description: '형태의 반복과 대비를 활용한 브랜드 실험', color: 'violet' },
  { image: 'https://i.pinimg.com/1200x/ce/61/bb/ce61bb3c4f4b3c46b5ab43713eb46331.jpg', category: 'web',      categoryLabel: 'Web design', title: 'Soft grid',         description: '유연한 그리드와 부드러운 정보 구조', color: 'peach' },
  { image: 'https://i.pinimg.com/1200x/9e/9d/31/9e9d3148debe729bf7c04847afd37caf.jpg', category: 'graphic',  categoryLabel: 'Graphic',     title: 'Light study',       description: '빛의 번짐과 흐림을 이용한 이미지 구성', color: 'cyan' },
  { image: 'https://i.pinimg.com/1200x/63/f8/6f/63f86fee8a81d7ef199fada362c4b97a.jpg', category: 'branding', categoryLabel: 'Branding',    title: 'New material',      description: '익숙한 소재를 새롭게 해석한 비주얼 언어', color: 'lime' },
  { image: 'https://i.pinimg.com/736x/8d/30/a0/8d30a046aba6c2b1920f300e6e6e7939.jpg',  category: 'web',      categoryLabel: 'Web design', title: 'Low contrast',      description: '낮은 대비 안에서도 선명해지는 인터페이스', color: 'night' },
  { image: 'https://i.pinimg.com/736x/74/b4/d0/74b4d05593f0e0becf56c28b4a6f01b2.jpg',  category: 'graphic',  categoryLabel: 'Graphic',     title: 'Raw texture',       description: '거친 표면과 정제된 레이아웃의 조합', color: 'indigo' },
  { image: 'https://i.pinimg.com/736x/ee/c4/ee/eec4ee08702bba12016cd1848627178f.jpg',  category: 'branding', categoryLabel: 'Branding',    title: 'Quiet identity',    description: '절제된 색과 비례감으로 만든 아이덴티티', color: 'rose' }
];

const grid       = document.querySelector('#referenceGrid');
const emptyState = document.querySelector('#emptyState');
const search     = document.querySelector('#referenceSearch');
const savedCount = document.querySelector('#savedCount');
const clearBtn   = document.querySelector('#clearSaved');

// 'saved' 면 저장한 것만 보여주는 화면이다.
const MODE = grid?.dataset.mode === 'saved' ? 'saved' : 'home';
let selectedCategory = 'all';

function esc(s) {
  return String(s).replace(/[&<>"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
}

/* ── 저장 목록 (브라우저에만 보관) ──
   localStorage 를 못 쓰는 환경에서도 화면이 깨지지 않도록 전부 감싼다. */
const SAVE_KEY = 'lumio.saved';
function loadSaved() {
  try {
    const v = JSON.parse(localStorage.getItem(SAVE_KEY));
    return Array.isArray(v) ? v : [];
  } catch (e) { return []; }
}
function storeSaved(list) {
  try { localStorage.setItem(SAVE_KEY, JSON.stringify(list)); } catch (e) {}
}
let saved = loadSaved();
const isSaved = (title) => saved.includes(title);

function toggleSave(title) {
  saved = isSaved(title) ? saved.filter(t => t !== title) : saved.concat(title);
  storeSaved(saved);
}

function render() {
  if (!grid) return;
  const query = (search?.value || '').trim().toLowerCase();

  const list = references.filter((r) => {
    if (MODE === 'saved' && !isSaved(r.title)) return false;
    const byCategory = selectedCategory === 'all' || r.category === selectedCategory;
    const bySearch = `${r.title} ${r.description} ${r.categoryLabel}`.toLowerCase().includes(query);
    return byCategory && bySearch;
  });

  grid.innerHTML = list.map((r) => {
    const on = isSaved(r.title);
    return `
    <article class="pin">
      <div class="pin-image ${r.color}">
        <img src="${r.image}" alt="${esc(r.title)}" loading="lazy"
             onerror="this.style.display='none'" />
        <button class="pin-save${on ? ' saved' : ''}" type="button"
                data-title="${esc(r.title)}"
                aria-pressed="${on}"
                aria-label="${esc(r.title)} ${on ? '저장 해제' : '저장'}">${on ? '♥' : '♡'}</button>
        <div class="pin-overlay">
          <strong>${esc(r.title)}</strong>
          <span>${esc(r.description)}</span>
        </div>
      </div>
      <p class="pin-label">${esc(r.categoryLabel)}</p>
    </article>`;
  }).join('');

  if (emptyState) emptyState.hidden = list.length !== 0;

  if (savedCount) savedCount.textContent = `${saved.length}개 저장됨`;
  if (clearBtn)   clearBtn.hidden = saved.length === 0;

  grid.querySelectorAll('.pin-save').forEach((b) => {
    b.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleSave(b.dataset.title);
      if (MODE === 'saved') {
        render();                   // 저장 화면에서는 해제하면 목록에서 빠진다
      } else {
        const on = isSaved(b.dataset.title);
        b.classList.toggle('saved', on);
        b.textContent = on ? '♥' : '♡';
        b.setAttribute('aria-pressed', String(on));
        b.setAttribute('aria-label', `${b.dataset.title} ${on ? '저장 해제' : '저장'}`);
      }
    });
  });
}

document.querySelectorAll('.chip[data-category]').forEach((chip) => {
  chip.addEventListener('click', () => {
    document.querySelector('.chip.active')?.classList.remove('active');
    chip.classList.add('active');
    selectedCategory = chip.dataset.category;
    render();
  });
});

clearBtn?.addEventListener('click', () => {
  saved = [];
  storeSaved(saved);
  render();
});

search?.addEventListener('input', render);
render();
