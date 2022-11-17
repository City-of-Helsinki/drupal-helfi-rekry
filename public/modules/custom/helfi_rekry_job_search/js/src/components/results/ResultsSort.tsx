import { Select } from 'hds-react';

import type OptionType from '../../types/OptionType';

type ResultsSortProps = {
  options: OptionType[];
  setValue: Function;
  value: OptionType | undefined;
};

const ResultsSort = ({ options, setValue, value }: ResultsSortProps) => {
  return (
    <Select
      label='Label'
      helper='Assistive text'
      placeholder='Placeholder'
      options={options}
      onChange={(option: OptionType) => setValue(option.value)}
      value={value || null}
    />
  );
};

export default ResultsSort;
