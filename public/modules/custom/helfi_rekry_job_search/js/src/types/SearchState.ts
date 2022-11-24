import type OptionType from './OptionType';

type SearchStateItem = {
  aggregations?: {
    [key: string]: {
      value: number;
    };
  };
  value: OptionType[];
  hits?: {
    hidden: number;
    hits: any[];
    time: number;
    total: number;
  };
};

type SearchState = {
  [key: string]: SearchStateItem;
};

export default SearchState;
