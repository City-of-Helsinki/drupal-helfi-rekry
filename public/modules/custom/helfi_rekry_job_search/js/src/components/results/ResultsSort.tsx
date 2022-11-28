import { Select } from 'hds-react';

import SearchComponents from '../../enum/SearchComponents';
import { setParams } from '../../helpers/Params';
import type OptionType from '../../types/OptionType';

type ResultsSortProps = {
  options: OptionType[];
  setValue: Function;
  value: OptionType | undefined;
};

const ResultsSort = ({ options, setValue, value }: ResultsSortProps) => {
  return (
    <Select
      label={Drupal.t('Order results', {}, { context: 'HELfi Rekry job search' })}
      options={options}
      onChange={(option: OptionType) => {
        setValue(option.value);
        setParams(
          {
            [SearchComponents.ORDER]: {
              value: option.value,
            },
          },
          [SearchComponents.ORDER]
        );
      }}
      value={value || null}
    />
  );
};

export default ResultsSort;
