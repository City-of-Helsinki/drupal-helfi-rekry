import SearchComponents from '../enum/SearchComponents';
import type InitialState from '../types/InitialState';
import type SearchState from '../types/SearchState';

const MASK_KEYS = [SearchComponents.RESULTS, SearchComponents.ORDER];

export const getInitialValues = () => {
  const searchParams = new URLSearchParams(window.location.search);

  let initialParams: InitialState = {};

  const initialPage = Number(searchParams.get(SearchComponents.RESULTS));
  if (initialPage) {
    initialParams.page = Number(initialPage);
  }

  const initialOrder = searchParams.get(SearchComponents.ORDER);
  if (initialOrder) {
    initialParams.order = initialOrder;
  }

  return initialParams;
};

const updateParams = (
  searchState: SearchState,
  searchParams: URLSearchParams = new URLSearchParams(),
  mask: string[] | null = null
) => {
  const keyArray = mask || MASK_KEYS;

  keyArray.forEach((key: string) => {
    if (!searchState[key]?.hasOwnProperty('value') || !keyArray.includes(key)) {
      return;
    }

    const value = searchState[key].value;
    if (Array.isArray(value)) {
      const transformedValue = value.map((selection) => selection.value);
      searchParams.set(key, JSON.stringify(transformedValue));
    } else if (value) {
      searchParams.set(key, value.toString());
    } else {
      searchParams.delete(key);
    }
  });

  return searchParams;
};

/**
 * Update URL parameters.
 * @param searchState current searchState
 * @param mask choose which params get updated
 */
export const setParams = (searchState: any, mask: string[] | null = null) => {
  const searchParams = new URLSearchParams(window.location.search);
  const transformedParams = updateParams(searchState, searchParams, mask);

  try {
    const allParamsString = transformedParams.toString();

    // If resulting string is the same as current one, do nothing.
    if (window.location.search === allParamsString) {
      return;
    }

    const newUrl = new URL(window.location.pathname, window.location.origin);
    newUrl.search = allParamsString;
    window.history.pushState({}, '', newUrl.toString());
  } catch (e) {
    console.warn('Error setting URL parameters.');
  }
};

export const clearParams = () => {
  const newUrl = new URL(window.location.pathname, window.location.origin);
  window.history.pushState({}, '', newUrl.toString());
};
