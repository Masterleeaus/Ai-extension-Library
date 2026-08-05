import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/categories/send_money/send_money_info_model.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/model/categories/send_money/check_user_with_qr_code.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../language/english.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class SendMoneyController extends GetxController {
  final copyInputController = TextEditingController();
  final amountController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
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

  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxString baseCurrency = "".obs;
  List<String> baseCurrencyList = [];

  void gotoPreview() {
    Get.toNamed(Routes.sendMoneyPreviewScreen);
  }

  @override
  void onInit() {
    getSendMoneyInfoData();
    amountController.text = "0";
    super.onInit();
  }

  // ---------------------------------------------------------------------------
  //                              Get Send Money Info Data
  // ---------------------------------------------------------------------------

  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late SendMoneyInfoModel _sendMoneyInfoModel;

  SendMoneyInfoModel get sendMoneyInfoModel => _sendMoneyInfoModel;

  // ------------------------------API Function---------------------------------
  //
  Future<SendMoneyInfoModel> getSendMoneyInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.sendMoneyInfoApi().then((value) {
      _sendMoneyInfoModel = value!;
      baseCurrency.value = _sendMoneyInfoModel.data.baseCurr;
      baseCurrencyList.add(_sendMoneyInfoModel.data.baseCurr);

      limitMin.value = _sendMoneyInfoModel.data.sendMoneyCharge.minLimit;
      limitMax.value = _sendMoneyInfoModel.data.sendMoneyCharge.maxLimit;
      dailyLimit.value = _sendMoneyInfoModel.data.sendMoneyCharge.dailyLimit;
      monthlyLimit.value =
          _sendMoneyInfoModel.data.sendMoneyCharge.monthlyLimit;
      percentCharge.value =
          _sendMoneyInfoModel.data.sendMoneyCharge.percentCharge;
      fixedCharge.value = _sendMoneyInfoModel.data.sendMoneyCharge.fixedCharge;
      rate.value = _sendMoneyInfoModel.data.baseCurrRate;
      
      //start remaing get
      remainingController.transactionType.value =
          _sendMoneyInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _sendMoneyInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _sendMoneyInfoModel.data.sendMoneyCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value =
          _sendMoneyInfoModel.data.baseCurr;

      remainingController.getRemainingBalanceProcess();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _sendMoneyInfoModel;
  }

  // ---------------------------------------------------------------------------
  //                             Check User Exist
  // ---------------------------------------------------------------------------

  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isCheckUserLoading = false.obs;

  bool get isCheckUserLoading => _isCheckUserLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late CommonSuccessModel _checkUserExistModel;

  CommonSuccessModel get checkUserExistModel => _checkUserExistModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> getCheckUserExistDate() async {
    _isCheckUserLoading.value = true;

    Map<String, dynamic> inputBody = {'email': copyInputController.text};
    update();

    await ApiServices.checkUserExistApi(body: inputBody).then((value) {
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

  // ---------------------------------------------------------------------------
  //                             Check User With Qr Code
  // ---------------------------------------------------------------------------

  // -------------------------------Define API Model-----------------------------
  //
  late CheckUserWithQrCodeModel _checkUserWithQrCodeModel;

  CheckUserWithQrCodeModel get checkUserWithQrCodeModel =>
      _checkUserWithQrCodeModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CheckUserWithQrCodeModel> getCheckUserWithQrCodeData(
      String qrcode) async {
    _isCheckUserLoading.value = true;

    Map<String, dynamic> inputBody = {'qr_code': qrcode};
    update();

    await ApiServices.checkUserWithQrCodeApi(body: inputBody).then((value) {
      _checkUserWithQrCodeModel = value!;
      copyInputController.clear();
      copyInputController.text = _checkUserWithQrCodeModel.data.userMobile;
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

  // ---------------------------------------------------------------------------
  //                             Check User Exist
  // ---------------------------------------------------------------------------
  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isSendMoneyLoading = false.obs;

  bool get isSendMoneyLoading => _isSendMoneyLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late CommonSuccessModel _sendMoneyModel;

  CommonSuccessModel get sendMoneyModel => _sendMoneyModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> sendMoneyProcess(context) async {
    _isSendMoneyLoading.value = true;

    Map<String, dynamic> inputBody = {
      'email': copyInputController.text,
      'amount': amountController.text,
    };
    update();

    await ApiServices.sendMoneyApi(body: inputBody).then((value) {
      _sendMoneyModel = value!;
      update();
      // Get.offAllNamed(Routes.bottomNavBarScreen);
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isSendMoneyLoading.value = false;
    update();
    return _sendMoneyModel;
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
