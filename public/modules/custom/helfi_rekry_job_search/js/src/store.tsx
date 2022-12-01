import { atom } from 'jotai';

// import SearchComponents from './enum/SearchComponents';

export const urlAtom = atom(Object.fromEntries(new URLSearchParams(window.location.search)), (get, set, value) => {
  //set atom value
  set(urlAtom, value);

  // Set new params to window.location
  // TODO Maybe do a separate writeAtom for this side-effect, for clarity's sake
  const url = get(urlAtom);
  const newUrl = new URL(window.location.toString());
  const newParams = new URLSearchParams();
  // eslint-disable-next-line array-callback-return
  for (const key in url) {
    if (url[key]) {
      newParams.set(key, url[key]);
    } else {
      newParams.delete(key);
    }
  }

  newUrl.search = newParams.toString();
  window.history.pushState({}, '', newUrl);
});

export const keywordAtom = atom('');
export const radioAtom = atom<null | string>(null);

// @ts-ignore
export const pageAtom = atom((get) => Number(get(urlAtom)?.page) || 1);
