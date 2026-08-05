import 'package:qrpay/backend/services/request_money_api_services.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/model/request_money/check_user_qr_scan.dart';
import '../../../backend/model/request_money/request_money_info_model.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class RequestMoneyController extends GetxController
    with RequestMoneyApiServices {
  final copyInputController = TextEditingController();
  final amountController = TextEditingController();
  final remarkController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());

  @override
  void onInit() {
    amountController.text = "0";
    getRequestMoneyInfoData();
    super.onInit();
  }

  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble totalFee = 0.0.obs;

  String enteredAmount = "";
  String transferFeeAmount = "";
  String totalCharge = "";
  String youWillGet = "";
  String payableAmount = "";
  RxString checkUserMessage = "".obs;
  RxBool isValidUser = false.obs;
  RxString baseCurrency = "".obs;
  List<String> baseCurrencyList = [];
   

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  final _isCheckUserLoading = false.obs;
  bool get isCheckUserLoading => _isCheckUserLoading.value;
  final _isRequestMoneyLoading = false.obs;
  bool get isRequestMoneyLoading => _isRequestMoneyLoading.value;

  /// Request money info process api
  late RequestMoneyInfoModel _requestMoneyInfoModel;
  RequestMoneyInfoModel get requestMoneyInfoModel => _requestMoneyInfoModel;
  Future<RequestMoneyInfoModel> getRequestMoneyInfoData() async {
    _isLoading.value = true;
    update();

    await getRequestMoneyInfoProcessApi().then((value) {
      _requestMoneyInfoModel = value!;
      baseCurrency.value = _requestMoneyInfoModel.data.baseCurr;
      baseCurrencyList.add(_requestMoneyInfoModel.data.baseCurr);

      limitMin.value = _requestMoneyInfoModel.data.requestMoneyCharge.minLimit;
      limitMax.value = _requestMoneyInfoModel.data.requestMoneyCharge.maxLimit;
      dailyLimit.value =
          _requestMoneyInfoModel.data.requestMoneyCharge.dailyLimit;
      monthlyLimit.value =
          _requestMoneyInfoModel.data.requestMoneyCharge.monthlyLimit;
      percentCharge.value =
          _requestMoneyInfoModel.data.requestMoneyCharge.percentCharge;
      fixedCharge.value =
          _requestMoneyInfoModel.data.requestMoneyCharge.fixedCharge;
      rate.value = _requestMoneyInfoModel.data.baseCurrRate;
      //start remaing get
      remainingController.transactionType.value =
          _requestMoneyInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _requestMoneyInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _requestMoneyInfoModel.data.requestMoneyCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value =
          _requestMoneyInfoModel.data.baseCurr;
      remainingController.getRemainingBalanceProcess();

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _requestMoneyInfoModel;
  }

  /// Check user by qr code
  late CheckUserQrCodeModel _checkUserWithQrCodeModel;
  CheckUserQrCodeModel get checkUserWithQrCodeModel =>
      _checkUserWithQrCodeModel;
  Future<CheckUserQrCodeModel> getCheckUserWithQrCodeData(String qrcode) async {
    _isCheckUserLoading.value = true;
    Map<String, dynamic> inputBody = {'qr_code': qrcode};
    update();
    await checkUserWithQrCodeApi(body: inputBody).then((value) {
      _checkUserWithQrCodeModel = value!;
      copyInputController.clear();
      copyInputController.text = _checkUserWithQrCodeModel.data.userEmail;
      isValidUser.value = true;
      update();
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isCheckUserLoading.value = false;
    update();
    return _checkUserWithQrCodeModel;
  }

  /// Check user
  late CommonSuccessModel _checkUserExistModel;
  CommonSuccessModel get checkUserExistModel => _checkUserExistModel;
  Future<CommonSuccessModel> getCheckUserExist() async {
    _isCheckUserLoading.value = true;
    Map<String, dynamic> inputBody = {'email': copyInputController.text};
    update();

    await checkUserExistApi(body: inputBody).then((value) {
      _checkUserExistModel = value!;
      checkUserMessage.value = _checkUserExistModel.message.success.first;
      isValidUser.value = true;
      update();
    }).catchError((onError) {
      checkUserMessage.value = Strings.notValidUser;
      isValidUser.value = false;
      log.e(onError);
    });
    _isCheckUserLoading.value = false;
    update();
    return _checkUserExistModel;
  }

  ///  Request money submit process
  late CommonSuccessModel _requestMoneyModel;
  CommonSuccessModel get requestMoneyModel => _requestMoneyModel;
  Future<CommonSuccessModel> requestMoneyProcess() async {
    _isRequestMoneyLoading.value = true;

    Map<String, dynamic> inputBody = {
      'email': copyInputController.text,
      'request_amount': amountController.text,
      'currency': baseCurrency.value,
      'remark': remarkController.text
    };
    update();

    await requestMoneySubmitURL(body: inputBody).then((value) {
      _requestMoneyModel = value!;
      update();
      // Get.offAllNamed(Routes.bottomNavBarScreen);
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isRequestMoneyLoading.value = false;
    update();
    return _requestMoneyModel;
  }

  RxDouble getFee({required double rate}) {
    double value = fixedCharge.value * rate;
    value = value +
        (double.parse(
                amountController.text.isEmpty ? '0.0' : amountController.text) *
            (percentCharge.value / 100));

    if (amountController.text.isEmpty) {
      totalFee.value = 0.0;
    } else {
      totalFee.value = value;
    }

    debugPrint(totalFee.value.toStringAsPrecision(2));
    return totalFee;
  }
}
