import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/model/categories/make_payment/check_merchant_scan_model.dart';
import '../../../backend/model/categories/make_payment/make_payment_info_model.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../language/english.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class MakePaymentController extends GetxController {
  final copyInputController = TextEditingController();
  final amountController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble baseCurrRate = 0.0.obs;
  RxDouble totalFee = 0.0.obs;
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;

  String enteredAmount = "";
  String transferFeeAmount = "";
  String totalCharge = "";
  String youWillGet = "";
  String payableAmount = "";
  RxString checkUserMessage = "".obs;
  RxBool isValidUser = false.obs;

  RxString baseCurrency = "".obs;
  List<String> baseCurrencyList = [];

  void gotoPreview() {
    Get.toNamed(Routes.makePaymentPreviewScreen);
  }

  @override
  void onInit() {
    amountController.text = "0";
    getMakePaymentInfoData();
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
  late MakePaymentInfoModel _makePaymentInfoModel;

  MakePaymentInfoModel get makePaymentInfoModel => _makePaymentInfoModel;

  // ------------------------------API Function---------------------------------
  //
  Future<MakePaymentInfoModel> getMakePaymentInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.makePaymentInfoApi().then((value) {
      _makePaymentInfoModel = value!;
      baseCurrency.value = _makePaymentInfoModel.data.baseCurr;
      baseCurrRate.value = _makePaymentInfoModel.data.baseCurrRate;
      baseCurrencyList.add(_makePaymentInfoModel.data.baseCurr);

      limitMin.value =
          _makePaymentInfoModel.data.makePaymentcharge.minLimit.toDouble();

      limitMax.value =
          _makePaymentInfoModel.data.makePaymentcharge.maxLimit.toDouble();
      dailyLimit.value =
          _makePaymentInfoModel.data.makePaymentcharge.dailyLimit.toDouble();
      monthlyLimit.value =
          _makePaymentInfoModel.data.makePaymentcharge.monthlyLimit.toDouble();

      percentCharge.value =
          _makePaymentInfoModel.data.makePaymentcharge.percentCharge.toDouble();
      fixedCharge.value =
          _makePaymentInfoModel.data.makePaymentcharge.fixedCharge.toDouble();
      //start remaing get
      remainingController.transactionType.value =
          _makePaymentInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _makePaymentInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _makePaymentInfoModel.data.makePaymentcharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value =
          _makePaymentInfoModel.data.baseCurr;
      remainingController.getRemainingBalanceProcess();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _makePaymentInfoModel;
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
  Future<CommonSuccessModel> getMerchantUserExistDate() async {
    _isCheckUserLoading.value = true;

    Map<String, dynamic> inputBody = {'email': copyInputController.text};
    update();

    await ApiServices.checkMerchantExistApi(body: inputBody).then((value) {
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
  late CheckMercantWithQrCodeModel _checkMerchantWithQrCodeModel;

  CheckMercantWithQrCodeModel get checkMerchantWithQrCodeModel =>
      _checkMerchantWithQrCodeModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CheckMercantWithQrCodeModel> getCheckMerchantWithQrCodeData(
      String qrcode) async {
    _isCheckUserLoading.value = true;

    Map<String, dynamic> inputBody = {'qr_code': qrcode};
    update();

    await ApiServices.checkMerchantWithQrCodeApi(body: inputBody).then((value) {
      _checkMerchantWithQrCodeModel = value!;
      copyInputController.clear();
      copyInputController.text =
          _checkMerchantWithQrCodeModel.data.merchantMobile;
      isValidUser.value = true;
      update();
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isCheckUserLoading.value = false;
    update();
    return _checkMerchantWithQrCodeModel;
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
  late CommonSuccessModel _makePaymentModelData;

  CommonSuccessModel get makePaymentModelData => _makePaymentModelData;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> makePaymentProcess() async {
    _isSendMoneyLoading.value = true;

    Map<String, dynamic> inputBody = {
      'email': copyInputController.text,
      'amount': amountController.text,
    };
    update();

    await ApiServices.makePaymentApi(body: inputBody).then((value) {
      _makePaymentModelData = value!;
      update();
      // Get.offAllNamed(Routes.bottomNavBarScreen);
    }).catchError((onError) {
      isValidUser.value = false;
      log.e(onError);
    });

    _isSendMoneyLoading.value = false;
    update();
    return _makePaymentModelData;
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
