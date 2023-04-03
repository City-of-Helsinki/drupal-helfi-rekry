import OptionType from '../types/OptionType';

export const sortOptions = (a: OptionType, b: OptionType) => {
  const aCount = a?.count || 0;
  const bCount = b?.count || 0;

  if (aCount < bCount) {
    return 1;
  }

  if (aCount > bCount) {
    return -1;
  }

  return 0;
};
