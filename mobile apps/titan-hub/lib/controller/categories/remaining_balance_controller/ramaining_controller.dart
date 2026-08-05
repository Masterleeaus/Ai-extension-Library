import 'package:get/get.dart';

import '../../../backend/model/remaining_blance_model/remaining_balance_model.dart';
import '../../../backend/services/api_services.dart';

class RemaingBalanceController extends GetxController {
  RxString transactionType = "".obs;
  RxString attribute = "".obs;
  RxString senderAmount = "0".obs;
  RxString senderCurrency = "".obs;
  RxInt cardId = 0.obs;
  RxDouble extraRate = 1.0.obs;

  // remaing limit
  dynamic remainingDailyLimit = 0.0.obs;
  dynamic remainingMonthLyLimit = 0.0.obs;

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;
  late RemainingBalanceModel _remainingBalanceModel;

  RemainingBalanceModel get remainingBalanceModel => _remainingBalanceModel;
  Future<RemainingBalanceModel> getRemainingBalanceProcess() async {
    _isLoading.value = true;
    update();

    await ApiServices.remainingBalanceAPi(
      transactionType.value,
      attribute.value,
      senderAmount.value,
      senderCurrency.value,
      cardId.value,
      extraRate.value,
    ).then((value) {
      _remainingBalanceModel = value!;

      remainingDailyLimit.value = _remainingBalanceModel.data.remainingDaily;

      senderCurrency.value = _remainingBalanceModel.data.currency;
      remainingMonthLyLimit.value =
          _remainingBalanceModel.data.remainingMonthly;

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
    });
    _isLoading.value = false;
    update();
    return _remainingBalanceModel;
  }
}
